<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExamenRequest;
use App\Models\ElementPaillasse;
use App\Models\Examen;
use App\Models\FamilyExam;
use App\Models\Prestation;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;


/**
 * @permission_category Gestion des examens
 */
class ExamenController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     *
     * @permission ExamenController::index
     * @permission_desc Afficher la liste des examens
     */
    public function index()
    {
        return response()->json(
            Examen::with([
                'paillasse',
                'subFamilyExam',
                'typePrelevement',
                'tubePrelevement',
                'kbPrelevement',
                'techniqueAnalysis',
                'elementPaillasses',
                'elementPaillasses.typeResult',
                'elementPaillasses.group_populations'
            ])
                ->when(request('search'), function ($query) {
                    $query->where('name', 'like', '%' . request('search') . '%')
                        ->orWhere('name1', 'like', '%' . request('search') . '%')
                        ->orWhere('name2', 'like', '%' . request('search') . '%')
                        ->orWhere('name3', 'like', '%' . request('search') . '%')
                        ->orWhere('name4', 'like', '%' . request('search') . '%')
                        ->orWhereHas('paillasse', function ($query) {
                            $query->whereLike('name', '%' . request('search') . '%');
                        });
                })
                ->paginate(
                    perPage: request('per_page', 25),
                    page: request('page', 1)
                )
        );
    }

    /**
     * Store a newly created examen in storage.
     *
     * @param ExamenRequest $request
     * @return JsonResponse
     *
     * @permission ExamenController::store
     * @permission_desc Ajout d’un examen
     * @throws \Throwable
     */
    public function store(ExamenRequest $request)
    {
        DB::beginTransaction();
        try {
            $examen = Examen::create($request->validated());

            foreach ($request->input('technique_analysis', []) as $item) {
                $examen->techniqueAnalysis()->attach($item['id'], [
                    'type' => $item['default'],
                ]);
            }

            foreach ($request->input('elements', []) as $item) {
                $element = $examen->elementPaillasses()->create($item);

                foreach ($item['normal_values'] as $normal_value) {
                    $element->group_populations()->attach($normal_value['populate_id'], [
                        'value' => $normal_value['value'],
                        'value_max' => $normal_value['value_max'],
                        'sign' => $normal_value['sign'],
                    ]);
                }
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'message' => "Erreur lors de l'ajout de l'examen",
                'error' => $e->getMessage(),
            ], 500);
        }
        DB::commit();

        return response()->json([
            'message' => 'Examen created successfully'
        ], ResponseAlias::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  Examen  $examen
     * @return Examen
     *
     * @permission ExamenController::show
     * @permission_desc Afficher un examen
     */
    public function show(Examen $examen)
    {
        return $examen->load([
            'paillasse',
            'subFamilyExam',
            'typePrelevement',
            'tubePrelevement',
            'kbPrelevement',
            'techniqueAnalysis',
            'elementPaillasses',
            'elementPaillasses.group_populations',
            'elementPaillasses.typeResult',
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ExamenRequest $request
     * @param Examen $examen
     * @return JsonResponse
     *
     * @permission ExamenController::update
     * @permission_desc Mettre a jour un examen
     * @throws \Throwable
     */
    public function update(ExamenRequest $request, Examen $examen)
    {
        DB::beginTransaction();
        try {
            $examen->update($request->validated());

            $examen->techniqueAnalysis()->detach();
            foreach ($request->input('technique_analysis', []) as $item) {
                $examen->techniqueAnalysis()->attach($item['id'], [
                    'type' => $item['default'],
                ]);
            }

            $elementPaillasse = $examen->elementPaillasses()->pluck('id')->toArray();
            $eltPaillasseIds = Arr::pluck($request->input('elements', []), 'id');
            $eltPaillasseDiff = array_diff($elementPaillasse, $eltPaillasseIds);
            ElementPaillasse::whereIn('id', $eltPaillasseDiff)->delete();

            foreach ($request->input('elements', []) as $item) {
                $element = ElementPaillasse::find($item['id']);

                if (!$element) {
                    $element = $examen->elementPaillasses()->create($item);
                }

                $element->update($item);

                $element->group_populations()->detach();
                foreach ($item['normal_values'] as $normal_value) {
                    $element->group_populations()->attach($normal_value['populate_id'], [
                        'value' => $normal_value['value'],
                        'value_max' => $normal_value['value_max'],
                        'sign' => $normal_value['sign'],
                    ]);
                }
            }
        }
        catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            Log::error($e->getLine());
            return response()->json([
                'message' => "Erreur lors de l'ajout de l'examen",
                'error' => $e->getMessage(),
            ], 500);
        }
        DB::commit();

        return response()->json([
            'message' => 'Examen updated successfully'
        ], ResponseAlias::HTTP_OK);
    }

    /**
     * Remove the specified examen from storage.
     *
     * @param Examen $examen
     * @return JsonResponse
     *
     * @permission ExamenController::destroy
     * @permission_desc Supprimer un examen
     */
    public function destroy(Examen $examen)
    {
        $examen->elementPaillasses()->delete();
        $examen->delete();

        return response()->json();
    }

    /**
     * @param Examen $examen
     * @param Prestation $prestation
     * @return JsonResponse
     *
     * @permission ExamenController::prelevement
     * @permission_desc Effectuer un prélèvement d’un examen
     */
    public function prelevement(Examen $examen, Prestation $prestation)
    {
        $prelevements = $prestation->examens()->find($examen->id)->pivot->prelevements;

        $prelevement = $prelevements ?? [];

        $prestation->examens()->updateExistingPivot($examen->id, [
            'prelevements' => [
                ...$prelevement,
                [
                    'id' => str()->uuid(),
                    'cancel' => false,
                    'preleve_date' => now(),
                ]
            ]
        ]);

        return response()->json([
            'message' => 'Examen prelevement successfully',
            'prestation' => $prestation->load([
                'payableBy:id,nomcomplet_client',
                'client',
                'consultant:id,nomcomplet',
                'priseCharge',
                'priseCharge.assureur',
                'priseCharge.quotation',
                'actes',
                'soins',
                'consultations',
                'medias',
                'hospitalisations',
                'products',
                'examens',
                'examens.kbPrelevement',
                'examens.typePrelevement',
                'examens.paillasse',
                'examens.subFamilyExam',
                'examens.subFamilyExam.familyExam',
            ])
        ], ResponseAlias::HTTP_OK);
    }

    /**
     * @param Prestation $prestation
     * @return JsonResponse
     *
     * @permission ExamenController::prelevementAll
     * @permission_desc Effectuer un prélèvement de tous les examens d’une prestation
     */
    public function prelevementAll(Prestation $prestation)
    {
        $examens = $prestation->examens;

        foreach ($examens as $examen) {
            $prelevements = $examen->pivot->prelevements;

            $prelevement = $prelevements ?? [];

            $prestation->examens()->updateExistingPivot($examen->id, [
                'prelevements' => [
                    ...$prelevement,
                    [
                        'id' => str()->uuid(),
                        'cancel' => false,
                        'preleve_date' => now(),
                    ]
                ]
            ]);
        }

        return response()->json([
            'message' => 'All examens prelevement successfully',
            'prestation' => $prestation->load([
                'payableBy:id,nomcomplet_client',
                'client',
                'consultant:id,nomcomplet',
                'priseCharge',
                'priseCharge.assureur',
                'priseCharge.quotation',
                'actes',
                'soins',
                'consultations',
                'medias',
                'hospitalisations',
                'products',
                'examens',
                'examens.kbPrelevement',
                'examens.typePrelevement',
                'examens.paillasse',
                'examens.subFamilyExam',
                'examens.subFamilyExam.familyExam',
            ])
        ], ResponseAlias::HTTP_OK);
    }

    /**
     * @param Prestation $prestation
     * @param Examen $examen
     * @param Request $request
     * @return JsonResponse
     *
     * @permission ExamenController::cancelPrelevement
     * @permission_desc Annuler un prélèvement d’un examen
     */
    public function cancelPrelevement(Prestation $prestation, Examen $examen, Request $request)
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'uuid'],
        ]);

        foreach ($request->input('ids') as $id) {
            $prelevements = $prestation->examens()->find($examen->id)->pivot->prelevements;

            if (!$prelevements) {
                return response()->json([
                    'message' => 'No prelevements found for this examen',
                ], ResponseAlias::HTTP_NOT_FOUND);
            }

            $prestation->examens()->updateExistingPivot($examen->id, [
                'prelevements' => array_map(function ($prelevement) use ($id) {
                    if ($prelevement['id'] === $id) {
                        $prelevement['cancel'] = true;
                        $prelevement['cancel_date'] = now();
                    }
                    return $prelevement;
                }, $prelevements)
            ]);
        }

        return response()->json([
            'message' => 'Prelevements cancelled successfully',
            'prestation' => $prestation->load([
                'payableBy:id,nomcomplet_client',
                'client',
                'consultant:id,nomcomplet',
                'priseCharge',
                'priseCharge.assureur',
                'priseCharge.quotation',
                'actes',
                'soins',
                'consultations',
                'medias',
                'hospitalisations',
                'products',
                'examens',
                'examens.kbPrelevement',
                'examens.typePrelevement',
                'examens.paillasse',
                'examens.subFamilyExam',
                'examens.subFamilyExam.familyExam',
            ])
        ], ResponseAlias::HTTP_OK);
    }



    /**
     * @param Prestation $prestation
     * @param Examen $examen
     * @param Request $request
     * @return JsonResponse
     *
     * @permission ExamenController::PrintTarifaireActes
     * @permission_desc Imprimer le tarifaire des examens
     */
    public function PrintTarifaireActes()
    {
        DB::beginTransaction();

        try {
            // Récupération des familles et de leurs examens triés
            $familles_examens = FamilyExam::with(['examens' => function ($query) {
                $query->orderBy('name')
                    ->with(['typePrelevement', 'tubePrelevement']);
            }])->orderBy('name')->get();

            $familles_examens = $familles_examens->filter(fn($famille) => $famille->examens->count() > 0);

            $data = [
                'title' => 'Tarifaire des examens',
                'familles_examens' => $familles_examens,
            ];


            // Nom du fichier et dossier
            $fileName   = 'tarifaires-examens-' . now()->format('YmdHis') . '.pdf';
            $folderPath = "storage/tarifaires-examens";
            $filePath   = $folderPath . '/' . $fileName;

            // Création du dossier si nécessaire
            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            // Génération du PDF
            save_browser_shot_pdf(
                view: 'pdfs.tarifaires-examens.tarifaires-examens', // Vue Blade
                data: $data,
                folderPath: $folderPath,
                path: $filePath,
                margins: [10, 10, 10, 10],
                format: 'A4',
            );

            // Vérifier si le PDF a été généré
            if (!file_exists($filePath)) {
                DB::rollBack();
                return response()->json(['error' => 'Le fichier PDF n\'a pas été généré.'], 500);
            }

            DB::commit();

            $pdfContent = file_get_contents($filePath);
            $base64 = base64_encode($pdfContent);

            return response()->json([
                'base64'   => $base64,
                'url'      => $filePath,
                'filename' => $fileName,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
