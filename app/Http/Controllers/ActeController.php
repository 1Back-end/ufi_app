<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActeRequest;
use App\Imports\ActesImport;
use App\Imports\MaladieImport;
use App\Models\Acte;
use App\Models\TypeActe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;


/**
 * @permission_category Gestion des actes
 */
class ActeController extends Controller
{
    /**
     * @return JsonResponse
     *
     * @permission ActeController::index
     * @permission_desc Afficher la liste des actes
     */
    public function index(Request $request)
    {
        if($request->input('actes')) {
            $query = Acte::with(['typeActe', 'createdBy:id,nom_utilisateur', 'updatedBy:id,nom_utilisateur']);

            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->input('search') . '%');
            }

            return response()->json([
                'actes' => $query->paginate($request->input('per_page', 10))
            ]);
        }

        return response()->json([
            'type_actes' => TypeActe::with(['actes', 'actes.createdBy:id,nom_utilisateur', 'actes.updatedBy:id,nom_utilisateur'])->get()
        ]);
    }

    public function import(Request $request)
    {

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new ActesImport(), $request->file('file'));
            return response()->json(['message' => 'Importation réussie.']);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }

    /**
     * @param ActeRequest $request
     * @return JsonResponse
     *
     * @permission ActeController::store
     * @permission_desc Enregistrer un acte
     */
    public function store(ActeRequest $request)
    {
        Acte::create($request->validated());
        return response()->json([
            'message' => __("Acte crée avec succès !")
        ],  201);
    }

    /**
     * @param ActeRequest $request
     * @param Acte $acte
     * @return JsonResponse
     *
     * @permission ActeController::update
     * @permission_desc Mettre à jour un acte
     */
    public function update(ActeRequest $request, Acte $acte)
    {
        $acte->update($request->validated());

        return response()->json([
            'message' => __('Mise à jour effectuée avec succès !')
        ], 202);
    }

    /**
     * @param Acte $acte
     * @param Request $request
     * @return JsonResponse
     *
     * @permission ActeController::changeStatus
     * @permission_desc Changer le status d'un acte
     */
    public function changeStatus(Acte $acte, Request $request)
    {
        $request->validate([
            "state" => ['required', 'boolean']
        ]);

        $acte->update(['state' => $request->input('state')]);

        return response()->json([
            'message' => __("Status mis à jour")
        ],202);
    }



    /**
     * @param Acte $acte
     * @param Request $request
     * @return JsonResponse
     *
     * @permission ActeController::PrintRapportActes
     * @permission_desc Imprimer la liste des tarifications des actes
     */
    public function PrintRapportActes()
    {
        DB::beginTransaction();

        try {
            // Récupérer tous les types avec leurs actes
            $types = TypeActe::with(['actes' => function ($query) {
                $query->where('state', true)->orderBy('name');
            }])
                ->orderBy('name')
                ->get();

            // Préparer les données pour la vue
            $data = [
                'title' => 'Tarifaire des actes',
                'types' => $types,
            ];


            // Nom du fichier et dossier
            $fileName   = 'rapport-actes-' . now()->format('YmdHis') . '.pdf';
            $folderPath = "storage/rapport-actes";
            $filePath   = $folderPath . '/' . $fileName;

            // Création dossier si nécessaire
            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            // Génération PDF
            save_browser_shot_pdf(
                view: 'pdfs.rapport-actes.rapports-actes', // Vue que tu crées pour l'affichage
                data: $data,
                folderPath: $folderPath,
                path: $filePath,
                margins: [10, 10, 10, 10],
                format: 'A4',
                direction: 'portrait'
            );

            DB::commit();


            $pdfContent = file_get_contents($filePath);
            $base64 = base64_encode($pdfContent);

            return response()->json([
                'base64'   => $base64,
                'url'      => $filePath,
                'filename' => $fileName,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Erreur de validation',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
