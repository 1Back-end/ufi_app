<?php

namespace App\Http\Controllers;

use App\Exports\AssureurExport;
use App\Exports\ClientsExport;
use App\Exports\ConsultantExportSearch;
use App\Imports\ClasseMaladieImport;
use App\Imports\MedecinImport;
use App\Imports\PescripteursImport;
use App\Models\Centre;
use App\Models\ConsultantDisponibilite;
use App\Models\Consultation;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Consultant;
use App\Models\Titre;
use App\Exports\ConsultantsExport;
use App\Enums\StatusConsultEnum;
use App\Enums\TelWhatsAppEnum;
use App\Enums\TypeConsultEnum;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Enum;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;
/**
 * @permission_category Gestion des consultants
 * @permission_module Gestion des prestations
 * @permission_module Gestion du laboratoire
 */
class ConsultantController extends Controller
{

    /**
     * @permission ConsultantController::index
     * @permission_desc Afficher la liste des consultants
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $consultants = Consultant::with([
            'code_hopi',
            'specialite',
            'code_titre',
            'disponibilites',
            'user',
            'creator',
            'updater',
            'prestations'
        ]);
        if ($request->filled('type')) {
            $consultants->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $consultants->where('status', $request->status);
        }
        if ($request->filled('code_hopi')) {
            $consultants->where('code_hopi', $request->code_hopi);
        }
        if ($request->filled('code_service_hopi')) {
            $consultants->where('code_service_hopi', $request->code_service_hopi);
        }
        if ($request->filled('code_specialite')) {
            $consultants->where('code_specialite', $request->code_specialite);
        }
        if ($request->filled('code_titre')) {
            $consultants->where('code_titre', $request->code_specialite);
        }
        // Recherche globale
        $consultants->when($request->input('search'), function ($query) use ($request) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {

                // Champs de consultant
                $q->where('ref', 'like', "%$search%")
                    ->orWhere('nom', 'like', "%$search%")
                    ->orWhere('prenom', 'like', "%$search%")
                    ->orWhere('nomcomplet', 'like', "%$search%")
                    ->orWhere('tel', 'like', "%$search%")
                    ->orWhere('tel1', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('type', 'like', "%$search%")
                    ->orWhere('status', 'like', "%$search%")
                    ->orWhere('id', 'like', "%$search%");

                // code_specialite
                $q->orWhereHas('specialite', function ($qq) use ($search) {
                    $qq->where('nom_specialite', 'like', "%$search%")
                        ->orWhere('id', 'like', "%$search%");
                });

                // code_titre
                $q->orWhereHas('code_titre', function ($qq) use ($search) {
                    $qq->where('nom_titre', 'like', "%$search%")
                        ->orWhere('abbreviation_titre', 'like', "%$search%")
                        ->orWhere('id', 'like', "%$search%");
                });

                $q->orWhereHas('code_hopi', function ($qq) use ($search) {
                    $qq->where('nom_hopi', 'like', "%$search%")
                        ->orWhere('Abbreviation_hopi', 'like', "%$search%")
                        ->orWhere('addresse_hopi', 'like', "%$search%")
                        ->orWhere('id', 'like', "%$search%");
                });

                $q->orWhereHas('user', function ($qq) use ($search) {
                    $qq->where('login', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%")
                        ->orWhere('nom_utilisateur', 'like', "%$search%")
                        ->orWhere('prenom', 'like', "%$search%");
                });

            });
        });

        $consultants = $consultants->latest()->paginate(
            perPage: $perPage,
            page: $page
        );

        return response()->json([
                'data' => $consultants->items(),
                'current_page' => $consultants->currentPage(),
                'last_page' => $consultants->lastPage(),
                'total' => $consultants->total(),
        ]);
    }


    /**
     * @permission ConsultantController::updateStatus
     * @permission_desc Mettre à jour le statut d'un consultant (Activer/Désactiver/Archiver)
     */
    public function updateStatus(Request $request, $id)
    {
        $consultant = Consultant::findOrFail($id);

        $request->validate([
            'is_active'   => 'sometimes|boolean',
            'is_archived' => 'sometimes|boolean',
        ]);

        if ($request->has('is_active')) {
            $consultant->is_active = $request->input('is_active');
        }

        if ($request->has('is_archived')) {
            $consultant->is_archived = $request->input('is_archived');
        }

        $consultant->save();

        return response()->json([
            'message' => 'Statut mis à jour avec succès.',
            'consultant' => $consultant
        ], 200);
    }

    /**
     * @permission ConsultantController::show
     * @permission_desc Afficher les détails d'un consultant
     */
    public function show(string $id)
    {
        $consultant = Consultant::with([
                'code_hopi',
                'specialite',
                'code_titre',
                'code_service_hopi',
                'disponibilites',
                'prestations.prestationType',
                'prestations.account',
                'account'
            ])
            ->findOrFail($id);
        return response()->json($consultant);
    }

    /**
     * @permission ConsultantController::export
     * @permission_desc Exporter la liste des consultants en excel
     */
    public function export()
    {
        $fileName = 'LISTE-DES-CONSULTANTS-' . Carbon::now()->format('Y-m-d_H-i-s') . '.xlsx';
        Excel::store(new ConsultantsExport(), $fileName, 'exportconsultants');
        return response()->json([
            "message" => "Exportation des données effectuée avec succès",
            "filename" => $fileName,
            "url" => Storage::disk('exportconsultants')->url($fileName)
        ]);
    }

    /**
     * @permission ConsultantController::store
     * @permission_desc Enregistrer un consultant
     */
    public function store(Request $request)
    {
        try {
            $auth = auth()->user();

            $data = $request->validate([
                'code_hopi' => 'required|exists:hopitals,id',
                'code_service_hopi' => 'required|exists:service__hopitals,id',
                'code_specialite' => 'required|exists:specialites,id',
                'code_titre' => 'required|exists:titres,id',

                'nom' => 'required|string',
                'prenom' => 'nullable|string',

                'order_number' => 'nullable|string|unique:consultants,order_number',

                'tel' => 'required|string|unique:consultants,tel',
                'tel1' => 'nullable|string|unique:consultants,tel1',
                'email' => 'nullable|email|unique:consultants,email',

                'type' => ['required', new Enum(TypeConsultEnum::class)],

                'TelWhatsApp' => 'nullable|boolean',
                'is_used_commission' => 'nullable|boolean',

                'jours' => 'nullable|array',
            ]);

            $data['TelWhatsApp'] = $request->boolean('TelWhatsApp');
            $data['is_used_commission'] = $request->boolean('is_used_commission');

            $data['created_by'] = $auth->id;

            $consultant = Consultant::create($data);

            $joursMap = [
                'Lundi' => 1,
                'Mardi' => 2,
                'Mercredi' => 3,
                'Jeudi' => 4,
                'Vendredi' => 5,
                'Samedi' => 6,
                'Dimanche' => 7,
            ];

            if (($data['type'] === TypeConsultEnum::INTERNE->value || $data['type'] === TypeConsultEnum::SUR_RENDEZ_VOUS->value) && !empty($request->jours)) {

                foreach ($request->jours as $jourNom => $plages) {

                    $jourInt = $joursMap[$jourNom] ?? null;
                    if (!$jourInt) continue;

                    foreach ($plages as $plage) {

                        if (empty($plage['heure_debut']) || empty($plage['heure_fin'])) {
                            continue;
                        }

                        ConsultantDisponibilite::create([
                            'consultant_id' => $consultant->id,
                            'jour' => $jourInt,
                            'heure_debut' => $plage['heure_debut'],
                            'heure_fin' => $plage['heure_fin'],
                            'created_by' => $auth->id,
                            'updated_by' => $auth->id,
                        ]);
                    }
                }
            }

            return response()->json([
                'message' => 'Consultant et disponibilités enregistrés avec succès.',
                'consultant' => $consultant->load('disponibilites'),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'message' => 'Erreur de validation des données.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Une erreur technique est survenue lors de l’enregistrement.',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * Display a listing of the resource.
     * @permission ConsultantController::update
     * @permission_desc Modifier un consultant
     */
    public function update(Request $request, $id)
    {
        try {
            $auth = auth()->user();

            $consultant = Consultant::findOrFail($id);

            $data = $request->validate([
                'code_hopi' => 'required|exists:hopitals,id',
                'code_service_hopi' => 'required|exists:service__hopitals,id',
                'code_specialite' => 'required|exists:specialites,id',
                'code_titre' => 'required|exists:titres,id',

                'nom' => 'required|string',
                'prenom' => 'nullable|string',


                'order_number' => 'nullable|string|unique:consultants,order_number,' . $consultant->id,

                'tel' => 'required|string|unique:consultants,tel,' . $consultant->id,
                'tel1' => 'nullable|string|unique:consultants,tel1,' . $consultant->id,
                'email' => 'nullable|email|unique:consultants,email,' . $consultant->id,

                'type' => ['required', new Enum(TypeConsultEnum::class)],

                'TelWhatsApp' => 'nullable|boolean',
                'is_used_commission' => 'nullable|boolean',

                'jours' => 'nullable|array',
            ]);

            $data['TelWhatsApp'] = $request->boolean('TelWhatsApp');
            $data['is_used_commission'] = $request->boolean('is_used_commission');

            $titre = Titre::find($data['code_titre']);

            if (!$titre) {
                return response()->json([
                    'message' => 'Titre introuvable',
                ], 404);
            }
            $data['nomcomplet'] = trim(
                $titre->nom_titre . ' ' . $data['nom'] . ' ' . $data['prenom']
            );

            $data['updated_by'] = $auth->id;

            $consultant->update($data);

            $joursMap = [
                'Lundi' => 1,
                'Mardi' => 2,
                'Mercredi' => 3,
                'Jeudi' => 4,
                'Vendredi' => 5,
                'Samedi' => 6,
                'Dimanche' => 7,
            ];

            ConsultantDisponibilite::where('consultant_id', $consultant->id)->delete();

            if (($data['type'] === TypeConsultEnum::INTERNE->value || $data['type'] === TypeConsultEnum::SUR_RENDEZ_VOUS->value) && !empty($request->jours)) {

                foreach ($request->jours as $jourNom => $plages) {

                    $jourInt = $joursMap[$jourNom] ?? null;
                    if (!$jourInt) continue;

                    foreach ($plages as $plage) {

                        if (empty($plage['heure_debut']) || empty($plage['heure_fin'])) {
                            continue;
                        }

                        ConsultantDisponibilite::create([
                            'consultant_id' => $consultant->id,
                            'jour' => $jourInt,
                            'heure_debut' => $plage['heure_debut'],
                            'heure_fin' => $plage['heure_fin'],
                            'created_by' => $auth->id,
                            'updated_by' => $auth->id,
                        ]);
                    }
                }
            }

            return response()->json([
                'message' => 'Le profil du consultant a été mis à jour avec succès !',
                'consultant' => $consultant->load('disponibilites'),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'message' => 'Erreur de validation des informations fournies.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Une erreur inattendue est survenue lors de la mise à jour du consultant.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function PlanningConsultant(Request $request)
    {
        $centreId = $request->header('centre');

        if (!$centreId) {
            return response()->json([
                'message' => 'Centre non fourni'
            ], 400);
        }
        try {
            DB::beginTransaction();

            $consultants = Consultant::with(['disponibilites','specialite','code_titre'])->orderBy('created_at', 'desc')->get();

            $centre = Centre::find($centreId);
            $media = $centre?->medias()->where('name', 'logo')->first();

            $data = [
                'logo' => $media ? 'storage/' . $media->path . '/' . $media->filename : '',
                'centre' => $centre,
                'consultants' => $consultants,
            ];

            $fileName   = 'planning-consultants-' . now()->format('YmdHis') . '.pdf';
            $folderPath = 'storage/planning-consultants';
            $filePath   = $folderPath . '/' . $fileName;

            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            // Génération du PDF
            save_browser_shot_pdf(
                view: 'pdfs.planning-consultants.planning-consultants',
                data: $data,
                folderPath: $folderPath,
                path: $filePath,
                margins: [10, 10, 10, 10]
            );

            DB::commit();

            if (!file_exists($filePath)) {
                return response()->json(['message' => 'Le fichier PDF n\'a pas été généré.'], 500);
            }

            $pdfContent = file_get_contents($filePath);
            $base64 = base64_encode($pdfContent);

            return response()->json([
                'message'  => 'Planning téléchargé avec succès.',
                'data'     => $data,
                'base64'   => $base64,
                'url'      => $filePath,
                'filename' => $fileName,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la génération du planning.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission ConsultantController::getDuplicates
     * @permission_desc Afficher la liste des consultants en doublon
     */
    public function getDuplicates(Request $request)
    {
        $consultants = Consultant::with(['specialite', 'code_hopi', 'prestations'])
            ->get();

        if ($consultants->isEmpty()) {
            return response()->json(['data' => [], 'total_groupes' => 0]);
        }

        $items = $consultants->map(function ($c) {
            return [
                'model'          => $c,
                'key_nomcomplet' => $this->normalizeName($c->nomcomplet),
                'key_nom_prenom' => $this->normalizeName($c->nom . ' ' . $c->prenom),
                'key_tel'        => $this->normalizePhone($c->tel),
                'key_tel1'       => $this->normalizePhone($c->tel1),
            ];
        })->values();

        $count = $items->count();

        $parent = range(0, $count - 1);

        $find = function ($x) use (&$parent, &$find) {
            if ($parent[$x] !== $x) {
                $parent[$x] = $find($parent[$x]);
            }
            return $parent[$x];
        };

        $union = function ($a, $b) use (&$parent, $find) {
            $rootA = $find($a);
            $rootB = $find($b);
            if ($rootA !== $rootB) {
                $parent[$rootA] = $rootB;
            }
        };

        $buckets = [
            'nomcomplet' => [],
            'nom_prenom' => [],
            'tel'        => [],
        ];

        foreach ($items as $i => $item) {
            if ($item['key_nomcomplet'] !== '') {
                $buckets['nomcomplet'][$item['key_nomcomplet']][] = $i;
            }
            if ($item['key_nom_prenom'] !== '') {
                $buckets['nom_prenom'][$item['key_nom_prenom']][] = $i;
            }
            if ($item['key_tel'] !== '') {
                $buckets['tel'][$item['key_tel']][] = $i;
            }
            if ($item['key_tel1'] !== '') {
                $buckets['tel'][$item['key_tel1']][] = $i;
            }
        }

        foreach ($buckets as $bucketGroup) {
            foreach ($bucketGroup as $indices) {
                if (count($indices) < 2) {
                    continue;
                }
                $first = $indices[0];
                for ($k = 1; $k < count($indices); $k++) {
                    $union($first, $indices[$k]);
                }
            }
        }

        $groups = [];
        for ($i = 0; $i < $count; $i++) {
            $root = $find($i);
            $groups[$root][] = $items[$i]['model'];
        }

        $duplicates = collect($groups)
            ->filter(fn ($group) => count($group) > 1)
            ->values()
            ->map(fn ($group) => collect($group));

        return response()->json([
            'data'              => $duplicates,
            'total_groupes'     => $duplicates->count(),
            'total_consultants' => $duplicates->sum(fn ($g) => $g->count()),
        ]);
    }

    private function normalizeName(?string $value): string
    {
        if (!$value) {
            return '';
        }

        $value = $this->removeAccents($value);
        $value = mb_strtoupper(trim($value));
        $value = preg_replace('/\s+/', ' ', $value);

        $words = array_filter(explode(' ', $value));
        sort($words);

        return implode(' ', $words);
    }

    private function normalizePhone(?string $value): string
    {
        if (!$value) {
            return '';
        }

        $digits = preg_replace('/\D+/', '', $value);

        if (strlen($digits) < 8) {
            return '';
        }

        return substr($digits, -9);
    }

    private function removeAccents(string $value): string
    {
        $unwanted = [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ä' => 'A',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Ö' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ç' => 'C', 'Ñ' => 'N',
        ];

        return strtr($value, $unwanted);
    }

}
