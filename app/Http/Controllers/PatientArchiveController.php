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

        // Filtrage par date (Personnalisé ou 3 jours par défaut)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();

            $query->whereBetween('created_at', [$startDate, $endDate]);
        } else {
            $query->whereBetween('created_at', [
                \Carbon\Carbon::today()->subDay()->startOfDay(),
                \Carbon\Carbon::today()->addDay()->endOfDay()
            ]);
        }

        if ($request->filled('first_visit_start') && $request->filled('first_visit_end')) {
            $query->whereBetween('first_visit_at', [
                \Carbon\Carbon::parse($request->first_visit_start)->startOfDay(),
                \Carbon\Carbon::parse($request->first_visit_end)->endOfDay()
            ]);
        }

        if ($request->filled('last_visit_start') && $request->filled('last_visit_end')) {
            $query->whereBetween('last_visit_at', [
                \Carbon\Carbon::parse($request->last_visit_start)->startOfDay(),
                \Carbon\Carbon::parse($request->last_visit_end)->endOfDay()
            ]);
        }

        // Recherche par mots-clés
        if ($search = trim($request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhere('number_order', 'like', "%{$search}%")

                    ->orWhereHas('patient', function ($qs) use ($search) {
                        $qs->where('nomcomplet_client', 'like', "%{$search}%")
                            ->orWhere('prenom_cli', 'like', "%{$search}%")
                            ->orWhere('nom_cli', 'like', "%{$search}%")
                            ->orWhere('secondprenom_cli', 'like', "%{$search}%")
                            ->orWhere('ref_cli', 'like', "%{$search}%")
                            ->orWhere('tel_cli', 'like', "%{$search}%");
                    })
                    ->orWhereHas('dossier', function ($qpr) use ($search) {
                        $qpr->where('code', 'like', "%{$search}%");
                    });
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
