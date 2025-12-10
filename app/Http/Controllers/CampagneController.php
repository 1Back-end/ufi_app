<?php

namespace App\Http\Controllers;

use App\Models\Campagne;
use App\Models\CampagneElement;
use App\Models\Centre;
use App\Models\Proforma;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CampagneController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission CampagneController::index
     * @permission_desc Afficher la liste des campagnes
     */

    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);  // Par défaut 25 éléments par page
        $page = $request->input('page', 1);

        $query = Campagne::with([
            'elements',
            'creator',
            'updater',
            'centre',

        ])
            ->where('centre_id', $request->header('centre'));
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%$search%")
                    ->orWhere('title', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%")
                    ->orWhere('price', 'like', "%$search%")
                    ->orWhere('start_date', 'like', "%$search%");
            });
        }

        // Exécution de la requête avec pagination
        $data = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        // Retour de la réponse JSON
        return response()->json([
            'data' => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'total' => $data->total(),
        ]);
    }


    /**
     * Display a listing of the resource.
     * @permission CampagneController::store
     * @permission_desc Création d'une campagne
     */
    public function store(Request $request)
    {
        $auth = auth()->user();
        $centreId = $request->header('centre');

        if (!$centreId) {
            return response()->json([
                'message' => __("Vous devez vous connecter à un centre !")
            ], Response::HTTP_UNAUTHORIZED);
        }

        // 🔹 Validation des données
        $validated = $request->validate([
            'title' => 'required|string',
            'abbreviation_unique' => 'required|string|unique:campagnes,abbreviation_unique',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'elements' => 'required|array|min:1',
            'elements.*.type' => 'required|in:examens,consultations,actes,soins',
            'elements.*.element_id' => 'required|integer',
            'elements.*.price' => 'required|numeric|min:0',
        ]);

        $centre = Centre::findOrFail($centreId);

        // 🔹 Génération du code : référence du centre + date + 7 caractères aléatoires
        $code = $centre->reference . now()->format('Ymd') . Str::upper(Str::random(7));

        // 🔹 Création de la campagne
        $campagne = Campagne::create([
            'code' => $code,
            'title' => $validated['title'],
            'abbreviation_unique' => $validated['abbreviation_unique'],
            'full_name' => strtoupper($validated['title'] . ' (' . $validated['abbreviation_unique'] . ')'),
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'centre_id' => $centre->id,
            'created_by' => $auth->id,
        ]);

        // 🔹 Création des éléments liés à la campagne
        foreach ($validated['elements'] as $el) {
            CampagneElement::create([
                'campagne_id' => $campagne->id,
                'type' => $el['type'],
                'element_id' => $el['element_id'],
                'price' => $el['price'],
                'created_by' => $auth->id,
            ]);
        }

        return response()->json([
            'message' => "Campagne créée avec succès",
            'campagne' => $campagne->load('elements')
        ], 201);
    }

    /**
     * Display a listing of the resource.
     * @permission CampagneController::update
     * @permission_desc Modification d'une campagne
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();
        $campagne = Campagne::findOrFail($id);
        $centreId = $request->header('centre');

        if (!$centreId) {
            return response()->json([
                'message' => __("Vous devez vous connecter à un centre !")
            ], Response::HTTP_UNAUTHORIZED);
        }

        // 🔹 Validation des données
        $validated = $request->validate([
            'title' => 'required|string',
            'abbreviation_unique' => 'required|string',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date', // date de fin > date de début
            'status' => 'nullable|in:active,inactive',
            'elements' => 'required|array|min:1',
            'elements.*.type' => 'required|in:examens,consultations,actes,soins',
            'elements.*.element_id' => 'required|integer',
            'elements.*.price' => 'required|numeric|min:0',
        ]);

        $centre = Centre::findOrFail($centreId);

        // 🔹 Génération du code : référence du centre + date + 7 caractères aléatoires
        $code = $centre->reference . now()->format('Ymd') . Str::upper(Str::random(7));

        // 🔹 Mise à jour des champs de la campagne
        $campagne->update([
            'code' => $code,
            'title' => $validated['title'],
            'abbreviation_unique' => $validated['abbreviation_unique'],
            'full_name' => strtoupper($validated['title'] . ' (' . $validated['abbreviation_unique'] . ')'),
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => $validated['status'] ?? $campagne->status,
            'updated_by' => $auth->id,
        ]);

        // 🔹 Gestion des éléments de campagne
        $existingElements = $campagne->elements()->pluck('id')->toArray();
        $newElements = [];

        foreach ($validated['elements'] as $el) {
            $existing = $campagne->elements()
                ->where('type', $el['type'])
                ->where('element_id', $el['element_id'])
                ->first();

            if ($existing) {
                $existing->update([
                    'price' => $el['price'],
                    'updated_by' => $auth->id,
                ]);
                $newElements[] = $existing->id;
            } else {
                $created = CampagneElement::create([
                    'campagne_id' => $campagne->id,
                    'type' => $el['type'],
                    'element_id' => $el['element_id'],
                    'price' => $el['price'],
                    'created_by' => $auth->id,
                ]);
                $newElements[] = $created->id;
            }
        }

        // 🔹 Supprimer les éléments retirés
        $elementsToDelete = array_diff($existingElements, $newElements);
        if (!empty($elementsToDelete)) {
            CampagneElement::whereIn('id', $elementsToDelete)->delete();
        }

        return response()->json([
            'message' => 'Campagne mise à jour avec succès',
            'campagne' => $campagne->load('elements')
        ]);
    }


    /**
     * Display a listing of the resource.
     * @permission CampagneController::show
     * @permission_desc Afficher les détails d'une campagne
     */
    public function show($id)
    {
        $campagne = Campagne::with(
            'elements',
            'centre'
        )->findOrFail($id);

        return response()->json([
            'campagne' => $campagne
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission CampagneController::changeStatus
     * @permission_desc Activer/Désactiver une campagne
     */
    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,inactive'
        ]);

        $campagne = Campagne::findOrFail($id);

        $campagne->update([
            'status' => $request->status,
            'updated_by' => auth()->id()
        ]);

        return response()->json([
            'message' => $request->status === 'active'
                ? 'Campagne activée avec succès'
                : 'Campagne désactivée avec succès',
            'campagne' => $campagne
        ]);
    }




    //
}
