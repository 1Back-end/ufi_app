<?php

namespace App\Http\Controllers;

use App\Exports\ExamenPhysiqueExport;
use App\Exports\NurseExport;
use App\Imports\NursesImport;
use App\Models\Nurse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class NurseController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission NurseController::index
     * @permission_desc Afficher la liste des infirmières
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = Nurse::where('is_deleted', false)
            ->with(['creator', 'editor']);

        // 🔍 Recherche par mot-clé
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('telephone', 'like', "%{$search}%")
                    ->orWhere('matricule', 'like', "%{$search}%")
                    ->orWhere('specialite', 'like', "%{$search}%")
                    ->orWhere('adresse', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        $results = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $results->items(),
            'current_page' => $results->currentPage(),
            'last_page' => $results->lastPage(),
            'total' => $results->total(),
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission NurseController::store
     * @permission_desc Création des infirmières
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        // Validation avec messages personnalisés
        $validated = $request->validate([
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'email' => 'required|email|unique:nurses,email',
            'telephone' => 'nullable|string|max:20',
            'specialite' => 'required|string|max:100',
            'adresse' => 'required|string|max:100',
        ], [
            'nom.required' => 'Le nom est obligatoire.',
            'prenom.required' => 'Le prénom est obligatoire.',
            'specialite.required' => 'Le specialite est obligatoire.',
            'adresse.required' => 'L\'adresse est obligatoire.',
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'L\'adresse e-mail doit être valide.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',

        ]);

        $validated['created_by'] = $auth->id;

        $nurse = Nurse::create($validated);
        $nurse->load(['creator', 'editor']);

        return response()->json([
            'nurse' => $nurse,
            'message' => 'Infirmière créée avec succès.'
        ], 201);
    }

    /**
     * Display a listing of the resource.
     * @permission NurseController::update
     * @permission_desc Modification des infirmières
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        $nurse = Nurse::where('is_deleted', false)->findOrFail($id);

        $validated = $request->validate([
            'nom' => 'sometimes|required|string|max:100',
            'prenom' => 'sometimes|required|string|max:100',
            'specialite' => 'sometimes|required|string|max:100',
            'adresse' => 'sometimes|required|string|max:100',
            'email' => [
                'sometimes', 'required', 'email',
                Rule::unique('nurses', 'email')->ignore($nurse->id),
            ],
            'telephone' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
        ], [
            'nom.required' => 'Le nom est obligatoire.',
            'prenom.required' => 'Le prénom est obligatoire.',
            'specialite.required' => 'Le specialite est obligatoire.',
            'adresse.required' => 'L\'adresse est obligatoire.',
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'L\'adresse e-mail doit être valide.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
        ]);

        $validated['updated_by'] = $auth->id;

        $nurse->update($validated);
        $nurse->load(['creator', 'editor']);

        return response()->json([
            'nurse' => $nurse,
            'message' => 'Infirmière mise à jour avec succès.'
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission NurseController::show
     * @permission_desc Afficher les détails des infirmières
     */
    public function show($id)
    {
        $data = Nurse::where('is_deleted', false)
            ->with(['creator', 'editor'])
            ->findOrFail($id);

        return response()->json([
            'data' => $data,
            'message' => 'Détails de l\'infirmière récupérés avec succès.'
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission NurseController::updateStatus
     * @permission_desc Changer le statut des infirmières
     */
    public function updateStatus(Request $request, $id)
    {
        $auth = auth()->user();

        $request->validate([
            'is_active' => 'required|boolean',
        ], [
            'is_active.required' => 'Le statut est requis.',
            'is_active.boolean' => 'Le statut doit être vrai ou faux.',
        ]);

        $nurse = Nurse::where('is_deleted', false)->findOrFail($id);

        $nurse->update([
            'is_active' => $request->is_active,
            'updated_by' => $auth->id,
        ]);

        return response()->json([
            'message' => 'Statut de l\'infirmière mis à jour avec succès.',
            'nurse' => $nurse
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission NurseController::export
     * @permission_desc Exporter la liste des infirmières
     */
    public function export(Request $request){
        $fileName = 'liste-des-infirmières-' . Carbon::now()->format('Y-m-d') . '.xlsx';

        Excel::store(new NurseExport(), $fileName, 'infirmieres');

        return response()->json([
            "message" => "Exportation des données effectuée avec succès",
            "filename" => $fileName,
            "url" => Storage::disk('infirmieres')->url($fileName)
        ]);

    }

    public function import(Request $request)
    {

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new NursesImport(), $request->file('file'));
            return response()->json(['message' => 'Importation réussie.']);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }




    //
}
