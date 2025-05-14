<?php

namespace App\Http\Controllers;

use App\Models\Assurable;
use App\Models\Assureur;
use App\Models\Consultation;
use App\Models\Acte;
use App\Models\OpsTblHospitalisation;
use App\Models\Soins;
use Illuminate\Http\Request;

class AssurableController extends Controller
{

    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante


        // Récupérer les assureurs avec pagination
        $assurables = Assurable::with('assureur:id,nom')
            ->paginate($perPage);


        return response()->json([
            'data' => $assurables->items(),
            'current_page' => $assurables->currentPage(),  // Page courante
            'last_page' => $assurables->lastPage(),  // Dernière page
            'total' => $assurables->total(),  // Nombre total d'éléments
        ]);
        //
    }

    public function storeConsultationPrices(Request $request)
    {
        // Validation des données envoyées par l'utilisateur
        $request->validate([
            'assureur_id' => 'required|exists:assureurs,id',
            'consultations' => 'required|array',
            'consultations.*.id' => 'required|exists:consultations,id',
            'consultations.*.pu' => 'required|numeric|min:0',
        ]);

        $saved = [];

        foreach ($request->consultations as $item) {
            $assurable = Assurable::updateOrCreate(
                [
                    'assureur_id' => $request->assureur_id,
                    'assurable_type' => Consultation::class,
                    'assurable_id' => $item['id'],

                ],
                [
                    'pu' => $item['pu'],  // Le prix saisi manuellement par l'utilisateur
                ]

            );
            Consultation::where('id',$item['id'])->update([
                'pu_default' => $item['pu'],
                'pu' => $item['pu'],
            ]);

            $saved[] = [
                'assureur_id'=> $assurable->assureur_id,
                'id' => $assurable->id,
                'pu' => $assurable->pu,
            ];
        }

        return response()->json([
            'message' => 'Prix des consultations enregistrés avec succès.',
            'data' => $saved,
        ], 201);
    }






    public function storeAssurablesSoins(Request $request)
    {
        try {
            $request->validate([
                'assureur_id' => 'required|exists:assureurs,id',
                'soins' => 'required|array',
                'soins.*.id' => 'required|exists:soins,id',
                'soins.*.pu' => 'required|numeric|min:0',
            ]);

            $saved = [];

            foreach ($request->soins as $item) {
                // Met à jour ou crée un prix pour l'assureur
                $assurable = Assurable::updateOrCreate(
                    [
                        'assureur_id' => $request->assureur_id,
                        'assurable_type' => Soins::class,
                        'assurable_id' => $item['id'],
                    ],
                    [
                        'pu' => $item['pu'],
                    ]
                );


                $saved[] = [
                    'assureur_id' => $assurable->assureur_id,
                    'id' => $assurable->id,
                    'pu' => $assurable->pu,
                ];
            }

            return response()->json([
                'message' => 'Prix des soins enregistrés avec succès.',
                'data' => $saved,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur est survenue lors de l\'enregistrement.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }



    public function storeHospitalisationsPrices(Request $request)
    {
        try {
            // Validation des données envoyées par l'utilisateur
            $request->validate([
                'assureur_id' => 'required|exists:assureurs,id',
                'hospitalisations' => 'required|array',
                'hospitalisations.*.id' => 'required|exists:ops_tbl_hospitalisation,id',
                'hospitalisations.*.pu' => 'required|numeric|min:0',
            ]);

            $saved = [];

            foreach ($request->hospitalisations as $item) {
                $assurable = Assurable::updateOrCreate(
                    [
                        'assureur_id' => $request->assureur_id,
                        'assurable_type' => Soins::class,
                        'assurable_id' => $item['id'],
                    ],
                    [
                        'pu' => $item['pu'],  // Le prix saisi manuellement par l'utilisateur
                    ]
                );

                $saved[] = [
                    'assureur_id'=> $assurable->assureur_id,
                    'id' => $assurable->id,
                    'pu' => $assurable->pu,
                ];
            }

            return response()->json([
                'message' => 'Prix des consultations enregistrés avec succès.',
                'data' => $saved,
            ], 201);
        } catch (\Exception $e) {
            // Capture l'exception et renvoie un message d'erreur détaillé
            return response()->json([
                'error' => 'Une erreur est survenue lors de l\'enregistrement.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }



    public function storeAssurablesActes(Request $request)
    {
        // Validation des données
        $request->validate([
            'assureur_id' => 'required|exists:assureurs,id',
            'actes' => 'required|array',
            'actes.*.id' => 'required|exists:actes,id',
            'actes.*.k_modulateur' => 'required|numeric|min:0',
            'actes.*.b' => 'required|numeric|min:0',
        ]);

        $saved = [];

        foreach ($request->actes as $item) {
            $assurable = Assurable::updateOrCreate(
                [
                    'assureur_id' => $request->assureur_id,
                    'assurable_type' => Acte::class,
                    'assurable_id' => $item['id'],
                ],
                [
                    'k_modulateur' => $item['k_modulateur'],
                    'b' => $item['b'],
                ]
            );
            Acte::where('id', $item['id'])->update([
                'k_modulateur' => $item['k_modulateur'],
                'b' => $item['b'],
            ]);

            $saved[] = [
                'assureur_id' => $assurable->assureur_id,
                'id' => $assurable->id,
                'k_modulateur' => $assurable->k_modulateur,
                'b' => $assurable->b,
            ];
        }

        return response()->json([
            'message' => 'Valeurs enregistrées avec succès pour les actes.',
            'data' => $saved,
        ], 201);
    }
    public function getConsultationsWithPrices(Request $request)
    {
        $request->validate([
            'assureur_id' => 'required|exists:assureurs,id',
        ]);

        $consultations = Consultation::all();
        $assurables = Assurable::where('assureur_id', $request->assureur_id)
            ->where('assurable_type', Consultation::class)
            ->get()
            ->keyBy('assurable_id');

        $data = $consultations->map(function ($consultation) use ($assurables) {
            $assurable = $assurables->get($consultation->id);

            return [
                'id' => $consultation->id,
                'name' => $consultation->name,
                'pu_default' => $consultation->pu_default,
                'pu' => $assurable ? $assurable->pu : $consultation->pu_default,
            ];
        });

        return response()->json(['data' => $data]);
    }







    //
}
