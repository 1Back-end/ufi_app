<?php

namespace App\Http\Controllers;
use App\Exports\AssureurExport;
use App\Exports\FournisseurExport;
use App\Exports\FournisseurSearchExport;
use App\Models\Centre;
use App\Models\Fournisseurs ;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @permission_category Gestion des fournisseurs
 * @permission_module Gestion des stocks
 */


class FournisseurController extends Controller
{

    /**
     * Display a listing of the resource.
     * @permission FournisseurController::index
     * @permission_desc Afficher la liste des fournisseurs
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = Fournisseurs::with(["creator", "updater"]);

        if ($search = trim($request->input('search'))) {
            $query->where(function ($query) use ($search) {

                $query->where('full_name', 'like', "%$search%")
                    ->orWhere('company_name', 'like', "%$search%")
                    ->orWhere('address', 'like', "%$search%")
                    ->orWhere('phone_number', 'like', "%$search%")
                    ->orWhere('second_phone_number', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('business_registration_number', 'like', "%$search%")
                    ->orWhere('website', 'like', "%$search%")
                    ->orWhere('city', 'like', "%$search%")
                    ->orWhere('country', 'like', "%$search%")
                    ->orWhere('tax_number', 'like', "%$search%")
                    ->orWhere('contact_person', 'like', "%$search%")
                    ->orWhere('contact_person_phone', 'like', "%$search%");
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
     * @permission FournisseurController::export_in_excel
     * @permission_desc Exporter la liste des fournisseurs en excel
     */
    public function export_in_excel(Request $request)
    {
        try {
            $fileName = strtoupper('LISTE-DES-FOURNISSEURS-' . Carbon::now()->format('Y-m-d') . '.xlsx');

            Excel::store(new FournisseurExport(), $fileName, 'exportfournisseurs');

            return response()->json([
                "message" => "Exportation des données effectuée avec succès",
                "filename" => $fileName,
                "url" => Storage::disk('exportfournisseurs')->url($fileName)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "message" => "Erreur lors de l'exportation des données",
                "error" => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display a listing of the resource.
         * @permission FournisseurController::export_in_pdf
     * @permission_desc Exporter la liste des fournisseurs en pdf
     */
    public function export_in_pdf(Request $request)
    {
        $centreId = $request->header('centre');

        if (!$centreId) {
            return response()->json([
                'message' => 'Centre non fourni'
            ], 400);
        }

        // Démarrage de la transaction pour sécuriser les opérations
        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            $fournisseurs = Fournisseurs::with(["creator", "updater"])
                ->orderBy('full_name', 'asc')
                ->get();

            $centre = Centre::find($centreId);
            $media = $centre?->medias()->where('name', 'logo')->first();

            $data = [
                'fournisseurs' => $fournisseurs,
                'logo' => $media ? 'storage/' . $media->path . '/' . $media->filename : '',
                'centre' => $centre,
            ];

            $fileName   = strtoupper('LISTE-DES-FOURNISSEURS-' . now()->format('YmdHis') . '.pdf');
            $folderPath = "storage/list-of-suplliers";
            $filePath   = $folderPath . '/' . $fileName;

            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            $footer = 'pdfs.reports.factures.footer';

            save_browser_shot_pdf(
                view: 'pdfs.list-of-suplliers.list-of-suplliers',
                data: $data,
                folderPath: $folderPath,
                path: $filePath,
                margins: [5, 8, 10, 8],
                footer: $footer,
            );

            if (!file_exists($filePath)) {
                \Illuminate\Support\Facades\DB::rollBack();
                return response()->json(['error' => 'Le fichier PDF n\'a pas été généré.'], 500);
            }

            \Illuminate\Support\Facades\DB::commit();

            $pdfContent = file_get_contents($filePath);
            $base64 = base64_encode($pdfContent);

            return response()->json([
                'base64'   => $base64,
                'url'      => $filePath,
                'filename' => $fileName,
            ], 200);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la génération du PDF',
                'error' => $e->getMessage()
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
        try {
            $fournisseur = Fournisseurs::with([
                'creator',
                'updater',
            ])->where('id', $id)->firstOrFail();

            return response()->json([
                'status'  => 'success',
                'message' => 'Fournisseur récupéré avec succès.',
                'fournisseur'    => $fournisseur
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Fournisseur introuvable.',
                'details' => $e->getMessage()
            ], 404);
        }
    }


    /**
     * Display a listing of the resource.
     * @permission FournisseurController::store
     * @permission_desc Enregistrer un fournisseur
     */

    public function store(Request $request)
    {
        try {

            $auth = auth()->user();

            if (!$auth) {
                return response()->json([
                    'message' => 'Vous devez être connecté pour effectuer cette action.'
                ], 401);
            }

            $data = $request->validate([
                'full_name' => 'required|string|max:255',

                'company_name' => 'required|string|max:255|unique:fournisseurs,company_name',

                'address' => 'required|string|max:255',

                'phone_number' => 'required|string|max:20|unique:fournisseurs,phone_number',

                'second_phone_number' => 'nullable|string|max:20|unique:fournisseurs,second_phone_number',

                'email' => 'required|email|max:255|unique:fournisseurs,email',

                'business_registration_number' => 'nullable|string|max:255|unique:fournisseurs,business_registration_number',

                'website' => 'nullable|string|max:255',

                'city' => 'required|string|max:255',

                'country' => 'required|string|max:255',

                'tax_number' => 'nullable|string|max:255|unique:fournisseurs,tax_number',

                'contact_person' => 'nullable|string|max:255',

                'contact_person_phone' => 'nullable|string|max:20|unique:fournisseurs,contact_person_phone',
            ], [

                'full_name.required' => 'Le nom complet est obligatoire.',

                'company_name.required' => 'Le nom de la société est obligatoire.',
                'company_name.unique' => 'Cette société existe déjà.',

                'address.required' => 'L\'adresse est obligatoire.',

                'phone_number.required' => 'Le numéro de téléphone est obligatoire.',
                'phone_number.unique' => 'Ce numéro de téléphone est déjà utilisé.',

                'second_phone_number.unique' => 'Ce second numéro est déjà utilisé.',

                'email.required' => 'L\'email est obligatoire.',
                'email.email' => 'Adresse email invalide.',
                'email.unique' => 'Cet email est déjà utilisé.',

                'business_registration_number.unique' => 'Ce registre de commerce existe déjà.',

                'tax_number.unique' => 'Ce numéro contribuable existe déjà.',

                'city.required' => 'La ville est obligatoire.',

                'country.required' => 'Le pays est obligatoire.',

                'contact_person_phone.unique' => 'Ce numéro de contact existe déjà.',
            ]);

            $data['created_by'] = $auth->id;
            $data['is_active'] = true;

            $fournisseur = Fournisseurs::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Fournisseur créé avec succès.',
                'data' => $fournisseur
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur de validation.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display a listing of the resource.
     * @permission FournisseurController::update
     * @permission_desc Modifier un fournisseur
     */

    public function update(Request $request, int $id)
    {
        try {

            $auth = auth()->user();

            if (!$auth) {
                return response()->json([
                    'message' => 'Vous devez être connecté pour effectuer cette action.'
                ], 401);
            }

            $fournisseur = Fournisseurs::find($id);

            if (!$fournisseur) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Fournisseur introuvable.'
                ], 404);
            }

            $data = $request->validate([
                'full_name' => 'required|string|max:255',

                'company_name' => 'required|string|max:255|unique:fournisseurs,company_name,' . $id,

                'address' => 'required|string|max:255',

                'phone_number' => 'required|string|max:20|unique:fournisseurs,phone_number,' . $id,

                'second_phone_number' => 'nullable|string|max:20|unique:fournisseurs,second_phone_number,' . $id,

                'email' => 'required|email|max:255|unique:fournisseurs,email,' . $id,

                'business_registration_number' => 'nullable|string|max:255|unique:fournisseurs,business_registration_number,' . $id,

                'website' => 'nullable|string|max:255',

                'city' => 'required|string|max:255',

                'country' => 'required|string|max:255',

                'tax_number' => 'nullable|string|max:255|unique:fournisseurs,tax_number,' . $id,

                'contact_person' => 'nullable|string|max:255',

                'contact_person_phone' => 'nullable|string|max:20|unique:fournisseurs,contact_person_phone,' . $id,

                'is_active' => 'sometimes|boolean',
            ]);

            $data['updated_by'] = $auth->id;

            $fournisseur->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Fournisseur mis à jour avec succès.',
                'data' => $fournisseur->fresh()
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur de validation.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue.',
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
        $request->validate([
            'is_active' => 'required|boolean',
        ],[
            'is_active.required' => 'Le statut est obligatoire.',
        ]);
        $type = Fournisseurs::where('id', $id)->first();
        $type->is_active = $request->is_active;
        $type->updated_by = $auth->id;
        $type->save();
        return response()->json([
            'success' => true,
            "message" => "Statut modifié avec succès"
        ]);
    }







    //
}
