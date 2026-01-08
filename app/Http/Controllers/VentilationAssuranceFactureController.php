<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\VentilationAssuranceFacture;
use Illuminate\Http\Request;
use Carbon\Carbon;

class VentilationAssuranceFactureController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ventilation_date' => 'required|date',
            'piece_number'     => 'required|string',
            'piece_date'       => 'required|date',
            'total_amount'     => 'required|numeric',
            'comment'          => 'nullable|string',
            'regulation_method_id' => 'required|exists:regulation_methods,id',
            'first_facture_date' => 'required|string', // dd/MM/yyyy
            'last_facture_date'  => 'required|string', // dd/MM/yyyy
        ]);

        // 🔹 Convertir les dates pour MySQL
        $validated['first_facture_date'] = Carbon::createFromFormat('d/m/Y', $validated['first_facture_date'])->format('Y-m-d');
        $validated['last_facture_date']  = Carbon::createFromFormat('d/m/Y', $validated['last_facture_date'])->format('Y-m-d');

        // 🔹 Créer la ventilation
        $ventilation = VentilationAssuranceFacture::create($validated + [
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

        // 🔹 Mettre à jour les factures de la période
        $start = Carbon::parse($validated['first_facture_date'])->startOfDay();
        $end   = Carbon::parse($validated['last_facture_date'])->endOfDay();

        $factures = Facture::with('prestation')
            ->whereBetween('date_fact', [$start, $end])
            ->get();

        foreach ($factures as $facture) {
            // Mettre à jour le state_facture
            $facture->state_facture = 2;
            $facture->save();

            // Mettre à jour le regulated de la prestation
            if ($facture->prestation) {
                $facture->prestation->regulated = 2;
                $facture->prestation->save();
            }
        }

        return response()->json([
            'message' => 'Ventilation créée avec succès et factures mises à jour.',
            'data'    => $ventilation
        ], 201);
    }

    //
}
