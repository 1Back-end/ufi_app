<?php

namespace App\Http\Controllers;

use App\Enums\TypePrestation;
use App\Http\Requests\PrestationRequest;
use App\Models\Facture;
use App\Models\Prestation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Exception;
use Symfony\Component\HttpFoundation\Response;

class PrestationController extends Controller
{
    public function typePrestation() {
        return TypePrestation::toArray();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @permission PrestationController::index
     * @permission_desc Afficher la liste des prestations
     */
    public function index(Request $request)
    {
        return response()->json([
            'prestations' => Prestation::with([
                'createdBy:id,nom_utilisateur',
                'updatedBy:id,nom_utilisateur',
                'payableBy:id,nomcomplet_client',
                'client',
                'consultant:id,nomcomplet_consult',
                'priseCharge:id,assureurs_id,taux_pc',
                'priseCharge.assureur:id,nom',
                'actes',
                'centre',
                'facture'
            ])
            ->latest()
            ->paginate(
                perPage: $request->input('per_page', 25),
                page: $request->input('page', 1)
            )
        ]);
    }

    /**
     * @param PrestationRequest $request
     * @return JsonResponse
     *
     * @permission PrestationController::store
     * @permission_desc Enregistrer une prestation
     * @throws \Throwable
     */
    public function store(PrestationRequest $request)
    {
        DB::beginTransaction();
        try {
            $centre = $request->header('centre');
            $data = array_merge($request->except(['remise', 'quantity', 'date_rdv']), ['centre_id' => $centre]);
            $prestation = Prestation::create($data);
            switch ($request->type){
                case TypePrestation::ACTES->value:
                    $prestation->actes()->attach($request->acte_id, [
                        'remise' => $request->input('remise'),
                        'quantity' => $request->input('quantity'),
                        'date_rdv' => $request->input('date_rdv')
                    ]);
                    break;
                default:
                    throw new Exception("Ce type de prestation n'est pas encore implémenté", Response::HTTP_BAD_REQUEST);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], $e->getCode() === 0 ? Response::HTTP_INTERNAL_SERVER_ERROR : Response::HTTP_BAD_REQUEST);
        }
        DB::commit();

        return response()->json([
            'message' => __("Prestation ajoutée avec succès !")
        ]);
    }

    /**
     * @param Prestation $prestation
     * @return JsonResponse
     */
    public function show(Prestation $prestation)
    {
        return response()->json([
            'prestation' => $prestation
        ]);
    }

    /**
     * @param PrestationRequest $request
     * @param Prestation $prestation
     * @return JsonResponse
     *
     * @permission PrestationController::update
     * @permission_desc Modifier une prestation
     * @throws \Throwable
     */
    public function update(PrestationRequest $request, Prestation $prestation)
    {
        DB::beginTransaction();
        try {
            $prestation->update($request->validated());
            switch ($request->type){
                case TypePrestation::ACTES->value:
                    $prestation->actes()->detach();
                    $prestation->actes()->attach($request->acte_id, [
                        'remise' => $request->input('remise'),
                        'quantity' => $request->input('quantity'),
                        'date_rdv' => $request->input('date_rdv')
                    ]);
                    break;
                default:
                    throw new Exception("Ce type de prestation n'est pas encore implémenté", Response::HTTP_BAD_REQUEST);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], $e->getCode() === 0 ? Response::HTTP_INTERNAL_SERVER_ERROR : Response::HTTP_BAD_REQUEST);
        }
        DB::commit();

        return response()->json([
            'message' => __("Prestation modifiée avec succès !")
        ]);
    }

    /**
     * @param Prestation $prestation
     * @param Request $request
     * @return JsonResponse
     * @throws \Throwable
     *
     * @permission PrestationController::saveFacture
     * @permission_desc Enregistrer une facture
     */
    public function saveFacture(Prestation $prestation, Request $request) {
        $request->validate([
            'proforma' => 'required|in:1,2',
        ]);

        if ($prestation->regulated) {
            return response()->json([
                'message' => __("Cette prestation a déjà une facture")
            ], Response::HTTP_BAD_REQUEST);
        }

        DB::beginTransaction();
        try {
            if ($prestation->facture) {
                $facture = $prestation->facture;
                $facture->date_fact = $request->proforma == 1 ? null : now();
                $facture->type = $request->proforma;
                $facture->save();
            }
            else {
                $amount = 0;
                $amount_pc = 0;
                $amount_remise = 0;

                switch ($prestation->type){
                    case TypePrestation::label(TypePrestation::ACTES->value):
                        $acteId = 0;
                        foreach ($prestation->actes as $acte) {
                            if ($prestation->priseCharge) {
                                $amount_pc = ($acte->pivot->quantity * $acte->pu * $prestation->priseCharge->taux_pc) / 100;
                            }
                            $amount_remise = ($acte->pivot->quantity * $acte->pu * $acte->pivot->remise) / 100;

                            $amount += ($acte->pivot->quantity * $acte->pu) - $amount_remise - $amount_pc;
                            $acteId = $acte->id;
                        }

                        $facture = Facture::create([
                            'prestation_id' => $prestation->id,
                            'date_fact' => $request->proforma == 1 ? null : now(),
                            'amount' => $amount,
                            'amount_pc' => $amount_pc,
                            'amount_remise' => $amount_remise,
                            'type' => $request->proforma
                        ]);

                        $code = 'F-' . Str::substr($prestation->centre->reference, 0, 4) . $acteId . date('dmy') . str_pad($facture->id, 5, '0', STR_PAD_LEFT);
                        $facture->update(['code' => $code]);

                        break;
                    default:
                        throw new Exception("Ce type de prestation n'est pas encore implémenté", Response::HTTP_BAD_REQUEST);
                }
            }

            $prestation->update(['regulated' => !($request->proforma == 1)]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        DB::commit();

        return response()->json([
            'message' => "Facture enregistrée avec succès !",
            'facture' => $facture
        ]);
    }
}
