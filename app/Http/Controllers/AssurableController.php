<?php

namespace App\Http\Controllers;

use App\Models\Assurable;
use App\Models\Assureur;
use App\Models\Consultation;
use App\Models\Acte;
use App\Models\OpsTblHospitalisation;
use App\Models\Soins;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssurableController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @permission AssurableController::store
     * @permission_desc Ajouter les prix uniques d’un assureur pour différents éléments dans le système
     */
    public function store(Request $request)
    {
        $request->validate([
            'assureur_id' => 'required|exists:assureurs,id',
            'consultations' => ['array'],
            'consultations.*.id' => 'exists:consultations,id',
            'consultations.*.pu' => 'numeric|min:0',
            'hospitalisations' => ['array'],
            'hospitalisations.*.id' => 'exists:ops_tbl_hospitalisation,id',
            'hospitalisations.*.pu' => 'numeric|min:0',
            'soins' => ['array'],
            'soins.*.id' => 'exists:soins,id',
            'soins.*.pu' => 'numeric|min:0',
            'actes' => ['array'],
            'actes.*.id' => 'exists:actes,id',
            'actes.*.b' => 'numeric|min:0',
            'actes.*.k_modulateur' => 'numeric|min:0',
        ]);

        $assureur = Assureur::find($request->assureur_id);

        $assureur->consultations()->detach();
        foreach ($request->consultations as $consultation) {
            $assureur->consultations()
                ->attach($consultation['id'], [
                    'pu' => $consultation['pu'],
                ]);
        }

        $assureur->hospitalisations()->detach();
        foreach ($request->hospitalisations as $hospitalisation) {
            $assureur->hospitalisations()
                ->attach($hospitalisation['id'], [
                    'pu' => $hospitalisation['pu'],
                ]);
        }

        $assureur->soins()->detach();
        foreach ($request->soins as $soin) {
            $assureur->soins()
                ->attach($soin['id'], [
                    'pu' => $soin['pu'],
                ]);
        }

        $assureur->actes()->detach();
        foreach ($request->actes as $acte) {
            $assureur->actes()
                ->attach($acte['id'], [
                    'b' => $acte['b'],
                    'k_modulateur' => $acte['k_modulateur']
                ]);
        }

        return response()->json([
            'message' => __("Eléments enregistrés avec succès ! ")
        ], 202);
    }
}
