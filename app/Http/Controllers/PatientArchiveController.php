<?php

namespace App\Http\Controllers;

use App\Models\PatientArchive;
use Illuminate\Http\Request;
/**
 * @permission_category Gestion des patients archivés
 * @permission_module Gestion des prestations
 */
class PatientArchiveController extends Controller
{

    /**
     * Display a listing of the resource.
     * @permission PatientArchiveController::index
     * @permission_desc Afficher la liste des dossiers des patients archivés
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = PatientArchive::with([
            'patient',
            'dossier',
            'creator',
            'updater',
            'location'
        ]);

        if ($search = trim($request->input('search'))) {
            $query->where(function ($subQ) use ($search) {
                $subQ->where('notes', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhere('number_order', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($clientQ) use ($search) {
                        $clientQ->where('nomcomplet_client', 'like', "%{$search}%")
                            ->orWhere('nom_cli', 'like', "%{$search}%")
                            ->orWhere('prenom_cli', 'like', "%{$search}%")
                            ->orWhere('tel_cli', 'like', "%{$search}%");
                    })
                    ->orWhereHas('dossier', function ($qpr) use ($search) {
                        $qpr->where('code', 'like', "%{$search}%");
                    });
            });
        } else {
            $query->when($request->filled('start_date') && $request->filled('end_date'), function ($q) use ($request) {
                $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
                $q->whereBetween('created_at', [$startDate, $endDate]);
            });
        }


        $data = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        $transformedItems = collect($data->items())->map(function ($archive) {
            $totalPatientArchives = PatientArchive::where('patient_id', $archive->patient_id)->count();
            $maxOrder = PatientArchive::where('patient_id', $archive->patient_id)->max('number_order');
            $isLatest = ($archive->number_order === $maxOrder);

            $archiveArray = $archive->toArray();
            $archiveArray['total_patient_dossiers'] = $totalPatientArchives;
            $archiveArray['is_latest_dossier'] = $isLatest;

            return $archiveArray;
        });

        return response()->json([
            'data'         => $transformedItems,
            'current_page' => $data->currentPage(),
            'last_page'    => $data->lastPage(),
            'total'        => $data->total(),
        ]);
    }
}
