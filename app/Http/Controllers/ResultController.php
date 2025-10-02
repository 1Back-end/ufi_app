<?php

namespace App\Http\Controllers;

use App\Enums\InputType;
use App\Enums\StateExamen;
use App\Enums\TypePrestation;
use App\Http\Requests\ResultRequest;
use App\Models\ElementPaillasse;
use App\Models\Examen;
use App\Models\Prestation;
use App\Models\Prestationable;
use App\Models\Result;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ResultController extends Controller
{
    /**
     * @param ResultRequest $request
     * @return JsonResponse
     *
     * @permission ResultController::store
     * @permission_desc Enregistrer un résultat
     * @throws Throwable
     */
    public function store(ResultRequest $request)
    {
        DB::beginTransaction();
        try {
            foreach ($request->data as $data) {
                $prestation = Prestation::find($data['prestation_id']);

                foreach ($data['results'] as $result) {
                    if (empty($result['result_machine'])) {
                        continue;
                    }

                    $element = ElementPaillasse::find($result['element_paillasse_id']);
                    $prestationable = Prestationable::where('prestation_id', $prestation->id)
                        ->where('prestationable_type', Examen::class)
                        ->where('prestationable_id', $element->examen_id)
                        ->first();
                    $resultExist = Result::where('prestation_id', $prestation->id)
                        ->where('element_paillasse_id', $result['element_paillasse_id'])
                        ->where('groupe_population_id', $result['groupe_population_id'])
                        ->first();

                    if ($element->typeResult->type != InputType::COMMENT->value) {
                        $prestationable->update([
                            'status_examen' => $prestationable->status_examen == StateExamen::CREATED || empty($result['result_machine'])
                                ? StateExamen::CREATED
                                : StateExamen::PENDING
                        ]);
                    }

                    if ($resultExist) {
                        $resultExist->update($result);
                        continue;
                    }

                    Result::create([
                        ...$result,
                        'prestation_id' => $prestation->id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return response()->json([
                "message" => __("Une erreur est survenue lors d'enregistrement des resultats !")
            ], 500);
        }
        DB::commit();

        return response()->json([
            "message" => __("Le(s) résultat(s) a été ajouté avec succès !")
        ], 201);
    }

    /**
     * @param ResultRequest $request
     * @return JsonResponse
     *
     * @permission ResultsController::update
     * @permission_desc Changer le status des résultats des examens
     */
    public function status(Request $request)
    {
        $request->validate([
            'data' => ['required', 'array'],
            'data.*.prestation_id' => ['required', 'integer', 'exists:prestations,id'],
            'data.*.examen_ids' => ['required', 'array'],
            'data.*.examen_ids.*' => ['required', 'integer', 'exists:examens,id'],
            'data.*.status' => ['required', 'string'],
        ]);

        foreach ($request->data as $data) {
            $prestation = Prestation::find($data['prestation_id']);

            foreach ($data['examen_ids'] as $examen_id) {
                $prestationable = Prestationable::where('prestation_id', $prestation->id)
                    ->where('prestationable_type', Examen::class)
                    ->where('prestationable_id', $examen_id)
                    ->first();
                $prestationable->update([
                    'status_examen' => $data['status']
                ]);
            }
        }

        return response()->json([
            "message" => __("Le status des examens a été modifié avec succès !")
        ], 202);
    }

    /**
     * @param ResultRequest $request
     * @return JsonResponse
     *
     * @permission ResultsController::validate
     * @permission_desc Valider le resultat des examens
     */
    public function validate(Request $request)
    {
        $request->validate([
            'data' => ['required', 'array'],
            'data.*.prestation_id' => ['required', 'integer', 'exists:prestations,id'],
            'data.*.examen_ids' => ['required', 'array'],
            'data.*.examen_ids.*' => ['required', 'integer', 'exists:examens,id'],
        ]);

        foreach ($request->data as $data) {
            $prestation = Prestation::find($data['prestation_id']);

            foreach ($data['examen_ids'] as $examen_id) {
                $prestationable = Prestationable::where('prestation_id', $prestation->id)
                    ->where('prestationable_type', Examen::class)
                    ->where('prestationable_id', $examen_id)
                    ->first();
                $prestationable->update([
                    'status_examen' => "validated"
                ]);
            }
        }

        return response()->json([
            "message" => __("Le resultat a été valider avec succès !")
        ], 202);
    }

    /**
     * @param ResultRequest $request
     * @return JsonResponse
     *
     * @permission ResultsController::cancel
     * @permission_desc Annuler la validation du resultat des examens
     */
    public function cancel(Request $request)
    {
        $request->validate([
            'data' => ['required', 'array'],
            'data.*.prestation_id' => ['required', 'integer', 'exists:prestations,id'],
            'data.*.examen_ids' => ['required', 'array'],
            'data.*.examen_ids.*' => ['required', 'integer', 'exists:examens,id'],
        ]);

        foreach ($request->data as $data) {
            $prestation = Prestation::find($data['prestation_id']);

            foreach ($data['examen_ids'] as $examen_id) {
                $prestationable = Prestationable::where('prestation_id', $prestation->id)
                    ->where('prestationable_type', Examen::class)
                    ->where('prestationable_id', $examen_id)
                    ->first();
                $prestationable->update([
                    'status_examen' => "pending"
                ]);

                // Supprimer le résultat 
                $examen = Examen::find($examen_id);
                if (!$examen) {
                    continue;
                }

                foreach ($examen->elementPaillasses as $elementPaillasse) {
                    Result::where('prestation_id', $prestation->id)
                        ->where('element_paillasse_id', $elementPaillasse->id)
                        ->forceDelete();
                }
            }
        }

        return response()->json([
            "message" => __("Le resultat a été annuler avec succès !")
        ], 202);
    }

    /**
     * Summary of destroy
     * @param Result $result
     * @return JsonResponse
     *
     * @permission ResultController::destroy
     * @permission_desc Supprimer un résultat
     */
    public function destroy(Result $result)
    {
        $result->delete();

        return response()->json();
    }
}
