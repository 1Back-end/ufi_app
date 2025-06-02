<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Specialite;
use App\Models\User;
use App\Models\Consultant;
use function Pest\Laravel\json;

class SpecialiteController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission SpecialiteController::index
     * @permission_desc Afficher l'id et le nom de la spécialité
     */
    public function index()
    {
        $specialites = Specialite::select('id','nom_specialite')
            ->where('is_deleted', false)
            ->get();
        return response()->json($specialites);
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }
    /**
     * Display a listing of the resource.
     * @permission SpecialiteController::get_all
     * @permission_desc Afficher toutes les spécialitées
     */
    public function get_all(Request $request){
        $perPage = $request->input('limit', 10);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante

        $specialites = Specialite::where('is_deleted', false)
            ->when($request->input('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where('nom_specialite', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%');
            })
            ->latest()->paginate(perPage: $perPage, page: $page);

        return response()->json([
            'data' => $specialites->items(),
            'current_page' => $specialites->currentPage(),  // Page courante
            'last_page' => $specialites->lastPage(),  // Dernière page
            'total' => $specialites->total(),  // Nombre total d'éléments
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * @permission SpecialiteController::store
     * @permission_desc Enregister une spécialité
     */
    public function store(Request $request)
    {
        // Validation des données d'entrée
        $validated = $request->validate([
            'nom_specialite' => 'required|unique:specialites,nom_specialite',  // Validation du champ obligatoire et unique
        ]);

        // Récupère l'utilisateur par défaut
        $auth = auth()->user();

        // Création du service hospitalier
        $specialite = Specialite::create([
            'nom_specialite' => $request->nom_specialite,
            'create_by_specialite' => $auth->id
        ]);

        // Retourne la réponse de succès
        return response()->json([
            'message' => 'Spécialité créé avec succès',
            'data' => $specialite
        ], 201);
        //
    }

    /**
     * Display the specified resource.
     * @permission SpecialiteController::show
     * @permission_desc Afficher les détails d'une spécialité
     */
    public function show(string $id)
    {
        $specialite = Specialite::find($id);
        if (!$specialite) {
            return response()->json([
                'message' => 'Spécialité introuvable',404
            ]);

        } else {
            return  response()->json($specialite);
        }
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     * @permission SpecialiteController::update
     * @permission_desc Mettre à jour une spécialité
     */
    public function update(Request $request, string $id)
    {
        // Récupérer l'utilisateur authentifié
        $auth = auth()->user();

        // Validation des données d'entrée
        $validated = $request->validate([
            'nom_specialite' => 'required|unique:specialites,nom_specialite,' . $id, // Correctif ici
        ]);

        // Trouver la spécialité par ID
        $specialite = Specialite::find($id);
        if (!$specialite) {
            return response()->json(['message' => 'Spécialité non trouvée'], 404);
        }

        // Mettre à jour les informations de la spécialité
        try {
            $specialite->update([
                'nom_specialite' => $request->nom_specialite,
                'update_by_specialite' => $auth->id, // L'utilisateur qui effectue la mise à jour
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }

        // Retourner la réponse de succès avec les données mises à jour
        return response()->json([
            'message' => 'Spécialité mise à jour avec succès',
            'data' => $specialite
        ], 200);
    }


    /**
     * Remove the specified resource from storage.
     * @permission SpecialiteController::destroy
     * @permission_desc Supprimer une spécialité
     */
    public function destroy(string $id)
    {
        // Find the Specialite by its ID
        $specialite = Specialite::find($id);

        if (!$specialite) {
            return response()->json(['message' => 'Spécialité non trouvée'], 404);
        }
        // Check if the specialité is associated with any consultants
        $consultantCount = Consultant::where('code_specialite', $specialite->id)->count();

        if ($consultantCount > 0) {
            return response()->json(['message' => 'La spécialité ne peut pas être supprimée car elle est associée à des consultants'], 400);
        }
        // Soft delete by setting is_deleted to true
        $specialite->is_deleted = true;
        $specialite->save();

        return response()->json([
            'message' => 'Spécialité supprimée avec succès'
        ]);
    }

}
