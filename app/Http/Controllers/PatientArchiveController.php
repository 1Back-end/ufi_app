<?php

namespace App\Http\Controllers;

use App\Models\PatientArchive;
use Illuminate\Http\Request;

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
            'updater'
        ]);

        // Filtrage par date
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        // Filtrage par first_visit_at
        if ($request->filled('first_visit_start') && $request->filled('first_visit_end')) {
            $query->whereBetween('first_visit_at', [$request->first_visit_start, $request->first_visit_end]);
        }

        // Filtrage par last_visit_at
        if ($request->filled('last_visit_start') && $request->filled('last_visit_end')) {
            $query->whereBetween('last_visit_at', [$request->last_visit_start, $request->last_visit_end]);
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

        // Pagination et retour
        $data = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'         => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page'    => $data->lastPage(),
            'total'        => $data->total(),
        ]);
    }

}
