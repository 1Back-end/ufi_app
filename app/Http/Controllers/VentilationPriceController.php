<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Prestation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class VentilationPriceController extends Controller
{

    public function getFacturesByPartenaire(Request $request, $id)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $start_date = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : null;
        $end_date = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : null;

        $query = Prestation::with([
            'centre',
            'factures',
            'client',
            'payableBy',
            'priseCharge',
        ])->where('payable_by', $id); // filtre par ID patient

        if ($start_date && $end_date) {
            $query->whereBetween('created_at', [$start_date, $end_date]);
        }

        $data = $query->latest()->paginate(
            perPage: $perPage,
            page: $page
        );

        return response()->json([
            'data' => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'total' => $data->total(),
        ]);
    }

    public function getPatientPartenaire(Request $request)
    {
        $type_cli = 'associate';
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = Client::with(['user', 'societe', 'prefix'])
            ->where('type_cli', $type_cli)
            ->where('status_cli', 1);

        // Filtrage par recherche si fourni
        if ($search = trim($request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('ref_cli', 'like', "%{$search}%")
                    ->orWhere('id', $search) // on peut garder exact pour l'id
                    ->orWhere('nomcomplet_client', 'like', "%{$search}%")
                    ->orWhere('prenom_cli', 'like', "%{$search}%")
                    ->orWhere('nom_cli', 'like', "%{$search}%")
                    ->orWhere('tel_cli', 'like', "%{$search}%")
                    ->orWhere('tel2_cli', 'like', "%{$search}%");
            });
        }

        $partenaires = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $partenaires->items(),
            'current_page' => $partenaires->currentPage(),
            'last_page' => $partenaires->lastPage(),
            'total' => $partenaires->total(),
        ]);
    }


    //
}
