<?php

namespace App\Http\Controllers;
use App\Exports\AssureurExport;
use App\Exports\FournisseurExport;
use App\Exports\FournisseurSearchExport;
use App\Models\Fournisseurs ;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @permission_category Gestion des fournisseurs
 */


class FournisseurController extends Controller
{

    /**
     * Display a listing of the resource.
     * @permission FournisseurController::index
     * @permission_desc Afficher la liste des fournisseurs avec pagination
     */
    public function index(Request $request){
        $perPage = $request->input('limit', 25);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante

        $query = Fournisseurs::with(["creator","updator"])->where('is_deleted',false);

        if ($search = trim($request->input('search'))) {
            $query->where(function ($query) use ($search) {
                $query->where('email', 'like', "%$search%")
                ->orWhere('adresse', 'like', '%' . $search . '%')
                    ->orWhere('tel', 'like', '%' . $search . '%')
                    ->orWhere('nom', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('personne_contact_1', 'like', '%' . $search . '%')
                    ->orWhere('personne_contact_2', 'like', '%' . $search . '%')
                    ->orWhere('telephone_contact_1', 'like', '%' . $search . '%')
                    ->orWhere('telephone_contact_2', 'like', '%' . $search . '%')
                    ->orWhere('directeur_general', 'like', '%' . $search . '%')
                    ->orWhere('registre_commerce', 'like', '%' . $search . '%')
                    ->orWhere('nui', 'like', '%' . $search . '%');
            });
        }

        $fournisseurs = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $fournisseurs->items(),
            'current_page' => $fournisseurs->currentPage(),
            'last_page' => $fournisseurs->lastPage(),
            'total' => $fournisseurs->total(),
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission FournisseurController::export
     * @permission_desc Exporter les données des fournisseurs
     */
    public function export()
    {
        try {
            // Nom du fichier avec la date actuelle
            $fileName = 'fournisseurs-' . Carbon::now()->format('Y-m-d') . '.xlsx';

            // Stockage du fichier Excel dans le disque 'exportfournisseurs'
            Excel::store(new FournisseurExport(), $fileName, 'exportfournisseurs');

            // Retourner une réponse JSON avec les informations de l'exportation
            return response()->json([
                "message" => "Exportation des données effectuée avec succès",
                "filename" => $fileName,
                "url" => Storage::disk('exportfournisseurs')->url($fileName) // Assurez-vous que le disque est correctement configuré dans config/filesystems.php
            ], 200);
        } catch (\Exception $e) {
            // Si une erreur se produit, retourner une réponse d'erreur
            return response()->json([
                "message" => "Erreur lors de l'exportation des données",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission FournisseurController::show
     * @permission_desc Afficher les détails d'un fournisseur
     */
    public function show($id)
    {
        $fournisseur = Fournisseurs::where('id', $id)
            ->where('is_deleted', false)
            ->first();

        if (!$fournisseur) {
            return response()->json(['message' => 'Fournisseur introuvable'], 404);
        }

        return response()->json($fournisseur, 200);
    }


    /**
     * Display a listing of the resource.
     * @permission FournisseurController::store
     * @permission_desc Enregistrer un fournisseur
     */

    public function store(Request $request)
    {
        try {
            // Authentifier l'utilisateur
            $auth = auth()->user();
            if (!$auth) {
                return response()->json([
                    'message' => 'Vous devez être connecté pour effectuer cette action.'
                ], 401);
            }

            // Validation des données entrantes avec messages personnalisés
            $data = $request->validate([
                'nom' => 'required|string|max:255',
                'adresse' => 'required|string|max:255',
                'tel' => 'required|string|unique:fournisseurs|max:20',
                'email' => 'required|email|unique:fournisseurs|max:255',
                'status' => 'sometimes|string|in:actif,inactif',
                'registre_commerce' => 'nullable|string|unique:fournisseurs|max:255',
                'nui' => 'nullable|string|unique:fournisseurs|max:255',
                'personne_contact_1' => 'nullable|string|max:255',
                'telephone_contact_1' => 'nullable|string|unique:fournisseurs|max:20',
                'personne_contact_2' => 'nullable|string|max:255',
                'telephone_contact_2' => 'nullable|string|unique:fournisseurs|max:20',
                'directeur_general' => 'nullable|string|max:255',
            ], [
                'nom.required' => 'Le nom du fournisseur est obligatoire.',
                'nom.string' => 'Le nom doit être une chaîne de caractères.',
                'nom.max' => 'Le nom ne peut pas dépasser 255 caractères.',

                'adresse.required' => 'L’adresse du fournisseur est obligatoire.',
                'adresse.string' => 'L’adresse doit être une chaîne de caractères.',
                'adresse.max' => 'L’adresse ne peut pas dépasser 255 caractères.',

                'tel.required' => 'Le numéro de téléphone est obligatoire.',
                'tel.string' => 'Le téléphone doit être une chaîne de caractères.',
                'tel.unique' => 'Ce numéro de téléphone est déjà utilisé.',
                'tel.max' => 'Le téléphone ne peut pas dépasser 20 caractères.',

                'email.required' => 'L’email est obligatoire.',
                'email.email' => 'L’email doit être une adresse email valide.',
                'email.unique' => 'Cet email est déjà utilisé.',
                'email.max' => 'L’email ne peut pas dépasser 255 caractères.',

                'registre_commerce.unique' => 'Ce registre de commerce est déjà utilisé.',
                'registre_commerce.max' => 'Le registre de commerce ne peut pas dépasser 255 caractères.',

                'nui.unique' => 'Ce NUI est déjà utilisé.',
                'nui.max' => 'Le NUI ne peut pas dépasser 255 caractères.',

                'personne_contact_1.max' => 'Le nom de la première personne à contacter ne peut pas dépasser 255 caractères.',
                'telephone_contact_1.unique' => 'Le téléphone de la première personne est déjà utilisé.',
                'telephone_contact_1.max' => 'Le téléphone de la première personne à contacter ne peut pas dépasser 20 caractères.',

                'personne_contact_2.max' => 'Le nom de la deuxième personne à contacter ne peut pas dépasser 255 caractères.',
                'telephone_contact_2.unique' => 'Le téléphone de la deuxième personne est déjà utilisé.',
                'telephone_contact_2.max' => 'Le téléphone de la deuxième personne à contacter ne peut pas dépasser 20 caractères.',

                'directeur_general.max' => 'Le nom du directeur général ne peut pas dépasser 255 caractères.',
            ]);

            // Ajouter l'ID de l'utilisateur authentifié
            $data['created_by'] = $auth->id;

            // Créer le fournisseur
            $fournisseur = Fournisseurs::create($data);

            return response()->json([
                'message' => 'Le fournisseur a été créé avec succès !',
                'fournisseur' => $fournisseur
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Erreur de validation des données.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la création du fournisseur.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display a listing of the resource.
     * @permission FournisseurController::update
     * @permission_desc Modifier un fournisseur
     */

    public function update(Request $request, $id)
    {
        try {
            // Authentifier l'utilisateur
            $auth = auth()->user();
            if (!$auth) {
                return response()->json([
                    'message' => 'Vous devez être connecté pour effectuer cette action.'
                ], 401);
            }

            // Vérifier si le fournisseur existe et n'est pas supprimé
            $fournisseur = Fournisseurs::where('id', $id)
                ->where('is_deleted', false)
                ->first();

            if (!$fournisseur) {
                return response()->json([
                    'message' => 'Fournisseur introuvable ou supprimé.'
                ], 404);
            }

            // Validation des données entrantes avec messages personnalisés
            $data = $request->validate([
                'nom' => 'required|string|max:255',
                'adresse' => 'required|string|max:255',
                'tel' => 'required|string|max:20|unique:fournisseurs,tel,' . $id,
                'email' => 'required|email|max:255|unique:fournisseurs,email,' . $id,
                'status' => 'sometimes|string|in:actif,inactif',
                'registre_commerce' => 'nullable|string|max:255|unique:fournisseurs,registre_commerce,' . $id,
                'nui' => 'nullable|string|max:255|unique:fournisseurs,nui,' . $id,
                'personne_contact_1' => 'nullable|string|max:255',
                'telephone_contact_1' => 'nullable|string|max:20|unique:fournisseurs,telephone_contact_1,' . $id,
                'personne_contact_2' => 'nullable|string|max:255',
                'telephone_contact_2' => 'nullable|string|max:20|unique:fournisseurs,telephone_contact_2,' . $id,
                'directeur_general' => 'nullable|string|max:255',
            ], [
                'nom.required' => 'Le nom du fournisseur est obligatoire.',
                'nom.string' => 'Le nom doit être une chaîne de caractères.',
                'nom.max' => 'Le nom ne peut pas dépasser 255 caractères.',

                'adresse.required' => 'L’adresse du fournisseur est obligatoire.',
                'adresse.string' => 'L’adresse doit être une chaîne de caractères.',
                'adresse.max' => 'L’adresse ne peut pas dépasser 255 caractères.',

                'tel.required' => 'Le numéro de téléphone est obligatoire.',
                'tel.string' => 'Le téléphone doit être une chaîne de caractères.',
                'tel.unique' => 'Ce numéro de téléphone est déjà utilisé.',
                'tel.max' => 'Le téléphone ne peut pas dépasser 20 caractères.',

                'email.required' => 'L’email est obligatoire.',
                'email.email' => 'L’email doit être une adresse email valide.',
                'email.unique' => 'Cet email est déjà utilisé.',
                'email.max' => 'L’email ne peut pas dépasser 255 caractères.',

                'registre_commerce.unique' => 'Ce registre de commerce est déjà utilisé.',
                'registre_commerce.max' => 'Le registre de commerce ne peut pas dépasser 255 caractères.',

                'nui.unique' => 'Ce NUI est déjà utilisé.',
                'nui.max' => 'Le NUI ne peut pas dépasser 255 caractères.',

                'personne_contact_1.max' => 'Le nom de la première personne à contacter ne peut pas dépasser 255 caractères.',
                'telephone_contact_1.unique' => 'Le téléphone de la première personne est déjà utilisé.',
                'telephone_contact_1.max' => 'Le téléphone de la première personne à contacter ne peut pas dépasser 20 caractères.',

                'personne_contact_2.max' => 'Le nom de la deuxième personne à contacter ne peut pas dépasser 255 caractères.',
                'telephone_contact_2.unique' => 'Le téléphone de la deuxième personne est déjà utilisé.',
                'telephone_contact_2.max' => 'Le téléphone de la deuxième personne à contacter ne peut pas dépasser 20 caractères.',

                'directeur_general.max' => 'Le nom du directeur général ne peut pas dépasser 255 caractères.',
            ]);

            // Ajouter l'ID de l'utilisateur qui met à jour
            $data['updated_by'] = $auth->id;

            // Mettre à jour le fournisseur
            $fournisseur->update($data);

            return response()->json([
                'message' => 'Le fournisseur a été mis à jour avec succès !',
                'fournisseur' => $fournisseur
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Erreur de validation des données.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la mise à jour du fournisseur.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission FournisseurController::destroy
     * @permission_desc Suppressions des fournisseurs
     */
    public function destroy($id)
    {
        $fournisseur = Fournisseurs::where('id', $id)
            ->where('is_deleted', false)
            ->first();

        if (!$fournisseur) {
            return response()->json(['message' => 'Fournisseur introuvable ou déjà supprimé'], 404);
        }

        try {
            $fournisseur->update(['is_deleted' => true]);

            return response()->json([
                'message' => 'Fournisseur supprimé avec succès'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la suppression du fournisseur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission FournisseurController::update_status
     * @permission_desc Activer/Désactiver un fournisseur
     */
    public function update_status(Request $request, $id)
    {
        $auth = auth()->user();
        $fournisseur = Fournisseurs::find($id);

        if (!$fournisseur || $fournisseur->is_deleted) {
            return response()->json(['message' => 'Fournisseur introuvable ou supprimé'], 404);
        }

        $status = $request->input('status');

        if (!in_array($status, ['actif', 'inactif'])) {
            return response()->json(['message' => 'Statut invalide'], 400);
        }

        $fournisseur->status = $status;
        $fournisseur->updated_by = $auth->id;
        $fournisseur->save();

        return response()->json([
            'message' => 'Statut mis à jour avec succès',
            'fournisseur' => $fournisseur
        ], 200);
    }







    //
}
