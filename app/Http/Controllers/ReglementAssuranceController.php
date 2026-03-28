<?php

namespace App\Http\Controllers;

use App\Models\ReglementAssurance;
use App\Models\ReglementFactureAssureur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReglementAssuranceController extends Controller
{
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            // ✅ Création du règlement principal
            $reglement = ReglementAssurance::create([
                'amount_total' => $request->amount_total,
                'ir_total' => $request->ir_total,
                'net_amount' => $request->net_amount,
                'apply_ir_global' => $request->apply_ir_global,
                'ir_rate_global' => $request->ir_rate_global,
                'assurance_id' => $request->assurance_id,
                'type' => $request->type ?? 'assurance',
                'reglement_date_sart' => $request->reglement_date_sart,
                'reglement_date_end' => $request->reglement_date_end,
                'created_by' => auth()->id(),
            ]);

            // ✅ Ajout des factures
            if (!empty($request->factures)) {
                foreach ($request->factures as $facture) {
                    ReglementFactureAssureur::create([
                        'reglement_assurance_id' => $reglement->id,
                        'facture_id' => $facture['facture_id'],
                        'montant_initial' => $facture['montant_initial'],
                        'montant_assure' => $facture['montant_assure'],
                        'montant_ir' => $facture['montant_ir'] ?? 0,
                        'montant_exclu' => $facture['montant_exclu'] ?? 0,
                        'type_label' => $facture['type_label'] ?? null,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Règlement enregistré avec succès',
                'data' => $reglement
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l’enregistrement',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
