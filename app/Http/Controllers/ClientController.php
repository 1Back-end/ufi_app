<?php

namespace App\Http\Controllers;

use App\DTO\ClientFilterData;
use App\Enums\StatusClient;
use App\Exports\ClientsExport;
use App\Http\Requests\ClientRequest;
use App\Models\CampagneFacture;
use App\Models\Centre;
use App\Models\Client;
use App\Models\ConventionAssocie;
use App\Models\PatientArchive;
use App\Models\Prefix;
use App\Models\Prestation;
use App\Models\PriseEnCharge;
use App\Models\Proforma;
use App\Models\RendezVous;
use App\Models\ResultatExamenCampagneFacture;
use App\Models\Sexe;
use App\Models\Societe;
use App\Models\StatusFamiliale;
use App\Models\TypeDocument;
use App\Models\User;
//use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Spatie\Browsershot\Browsershot;
use Spatie\Browsershot\Exceptions\CouldNotTakeBrowsershot;
use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception;
use Symfony\Component\HttpFoundation\Response;
use Throwable;


/**
 * @permission_category Gestion des clients
 * @permission_module Gestion des prestations
 * @permission_module Gestion du laboratoire
 */
class ClientController extends Controller
{
    /**
     * Returns initial data for the client form.
     *
     * @return JsonResponse
     */
    public function initData()
    {
        // Get all the necessary data for the form
        $societes = Societe::select('id', 'nom_soc_cli')->get()->toArray();
        $typeDocuments = TypeDocument::select('description_typedoc', 'id')->get()->toArray();
        $prefixes = Prefix::select(['prefixe', 'id', 'position', 'age_max', 'age_min'])->with(['sexes:id,description_sex'])->get()->toArray();
        $statusFamiliales = StatusFamiliale::select(['description_statusfam', 'id'])->with(['sexes:id,description_sex'])->get()->toArray();
        $sexes = Sexe::select(['description_sex', 'id'])->with(['prefixes:id,prefixe,position', 'status_families:id,description_statusfam'])->get()->toArray();
        $centres = Centre::select('name', 'id')->get()->toArray();

        // Return the data as a JSON response
        return response()->json([
            'societes' => $societes,
            'typeDocuments' => $typeDocuments,
            'prefixes' => $prefixes,
            'statusFamiliales' => $statusFamiliales,
            'sexes' => $sexes,
            'centres' => $centres
        ], Response::HTTP_OK);
    }

    /**
     * Get a list of clients based on search query.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @permission ClientController::index
     * @permission_desc Afficher la liste de client via le filtre
     */
    public function index(Request $request)
    {

        $clients = client_filter(
            ClientFilterData::fromRequest($request)
        );

        return response()->json($clients, Response::HTTP_OK);
    }

    /**
     * Create a new client and add a reference to it.
     *
     * @param ClientRequest $request
     * @return JsonResponse
     *
     * @permission ClientController::store
     * @permission_desc Créer un client
     * @throws \Throwable
     */
    public function store(ClientRequest $request)
    {
        if (!$request->header('centre')) {
            return \response()->json([
                'message' => __("Vous devez vous connectez à un centre !")
            ], Response::HTTP_UNAUTHORIZED);
        }

        $dataValidated = $request->validated();
        $dataValidated['site_id'] = $request->header('centre');

        // 1. Génération de la référence officielle du centre EN AMONT
        $centre = Centre::find($dataValidated['site_id']);

        $lastEntryUserForThisYear = DB::table('user_centre')
            ->where('centre_id', $centre->id)
            ->whereYear('created_at', now()->year)
            ->orderBy('sequence', 'desc')
            ->first();

        $id = $lastEntryUserForThisYear ? $lastEntryUserForThisYear->sequence + 1 : 1;
        $refCli = $centre->reference . now()->year . Str::padLeft($id, 6, '0');

        $dataValidated['ref_cli'] = $refCli;

        if (!empty($dataValidated['client_anonyme_cli'])) {
            $nomSaisi = $dataValidated['nom_cli'] ?? 'XXX';
            $initiales = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $nomSaisi), 0, 3));
            if (strlen($initiales) < 3) {
                $initiales = Str::padRight($initiales, 3, 'X');
            }

            $genre = ($dataValidated['sexe_id'] == 1) ? 'M' : 'F';

            $dataValidated['nom_cli'] = $dataValidated['nom_cli'] ?? 'XXX';

            $dataValidated['nomcomplet_client'] = $genre . '-' . $initiales . '-' . $refCli;

            $dataValidated['tel_cli'] = '00000000';
            $dataValidated['prenom_cli'] = null;
            $dataValidated['secondprenom_cli'] = null;
            $dataValidated['email'] = null;
            $dataValidated['addresse_cli'] = null;
            $dataValidated['nom_conjoint_cli'] = null;
            $dataValidated['prenom_conjoint_cli'] = null;
            $dataValidated['document_number_cli'] = null;
        } else {
            $nom = $dataValidated['nom_cli'] ?? '';
            $prenom = $dataValidated['prenom_cli'] ?? '';
            $dataValidated['nomcomplet_client'] = trim(strtoupper($nom) . ' ' . $prenom);
        }

        $dataValidated['date_naiss_cli'] = $dataValidated['date_naiss_cli_estime']
            ? now()->subYears($dataValidated['age'])->year . '-01-01'
            : $dataValidated['date_naiss_cli'];

        $dataValidated['enfant_cli'] = isset($dataValidated['date_naiss_cli'])
            ? Carbon::parse($dataValidated['date_naiss_cli'])->age <= 14
            : false;

        unset($dataValidated['age']);

        DB::beginTransaction();
        try {
            $client = Client::create($dataValidated);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Une erreur est survenue lors de la création du client',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        DB::commit();

        return response()->json([
            'message' => 'Client a été créé avec succès !',
            'client' => $client
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param Client $client
     * @return JsonResponse
     *
     * @permission ClientController::show
     * @permission_desc Afficher un client
     */
    public function show(Client $client)
    {
        // Return the client with its relations
        return response()->json($client->load('user', 'societe', 'prefix', 'typeDocument', 'sexe', 'statusFamiliale', 'createByCli', 'updateByCli'), Response::HTTP_OK);
    }

    /**
     * @param ClientRequest $request
     * @param Client $client
     * @return JsonResponse
     *
     * @permission ClientController::update
     * @permission_desc Mise à jour d’un client
     */
    public function update(ClientRequest $request, Client $client)
    {
        $dataValidated = $request->validated();

        if (!empty($dataValidated['client_anonyme_cli'])) {
            if (!$client->client_anonyme_cli) {
                $nomSaisi = $dataValidated['nom_cli'] ?? $client->nom_cli ?? 'XXX';
                $initiales = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $nomSaisi), 0, 3));
                if (strlen($initiales) < 3) {
                    $initiales = Str::padRight($initiales, 3, 'X');
                }

                $genre = ($dataValidated['sexe_id'] == 1) ? 'M' : 'F';

                $dataValidated['nom_cli'] = $dataValidated['nom_cli'] ?? $client->nom_cli ?? 'XXX';

                $dataValidated['nomcomplet_client'] = $genre . '-' . $initiales . '-' . $client->ref_cli;

                $dataValidated['tel_cli'] = '00000000';
                $dataValidated['prenom_cli'] = null;
                $dataValidated['secondprenom_cli'] = null;
                $dataValidated['email'] = null;
                $dataValidated['addresse_cli'] = null;
                $dataValidated['nom_conjoint_cli'] = null;
                $dataValidated['prenom_conjoint_cli'] = null;
                $dataValidated['document_number_cli'] = null;
            } else {
                unset($dataValidated['nom_cli']);
                unset($dataValidated['nomcomplet_client']);
                unset($dataValidated['prenom_cli']);
                unset($dataValidated['secondprenom_cli']);
            }
        } else {

            $nom = $dataValidated['nom_cli'] ?? $client->nom_cli;
            $prenom = $dataValidated['prenom_cli'] ?? $client->prenom_cli;

            $dataValidated['nomcomplet_client'] = trim(strtoupper($nom) . ' ' . $prenom);
            $dataValidated['client_anonyme_cli'] = false;
        }

        $dataValidated['date_naiss_cli'] = $dataValidated['date_naiss_cli_estime']
            ? now()->subYears($dataValidated['age'])->year . '-01-01'
            : $dataValidated['date_naiss_cli'];

        $dataValidated['enfant_cli'] = isset($dataValidated['date_naiss_cli'])
            ? Carbon::parse($dataValidated['date_naiss_cli'])->age <= 14
            : false;

        unset($dataValidated['age']);

        $client->update($dataValidated);

        return response()->json([
            'message' => 'Client a été mis à jour avec succès !',
            'client' => $client->refresh()
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * Supprime un client
     *
     * @param Client $client
     * @return JsonResponse
     *
     * @permission ClientController::destroy
     * @permission_desc Supprimer un client
     */
    public function destroy(Client $client)
    {
        if ($client->prestations()->exists()) {
            return response()->json([
                'message' => "Impossible de supprimer ce client : des prestations sont déjà enregistrées."
            ], Response::HTTP_FORBIDDEN);
        }

        $client->delete();

        return response()->json([
            'message' => 'Client a été supprimé avec succès !',
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * Update the status of the specified client.
     *
     * @param Client $client
     * @param Request $request
     * @return JsonResponse
     *
     * @permission ClientController::updateStatus
     * @permission_desc Mise à jour du status d'un client
     */
    public function updateStatus(Client $client, Request $request)
    {
        $request->validate([
            'status' => ['required', new Enum(StatusClient::class)]
        ]);

        // Update the client's status
        $client->update(['status_cli' => $request->status]);

        // Determine the status text for the response message
        $text = $request->status === 1 ? 'activé' : ($request->status === 0 ? 'désactivé' : 'archivé');

        // Return a JSON response with a success message
        return response()->json([
            'message' => "Client a été $text avec succès !",
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     *
     * @permission ClientController::export
     * @permission_desc Exporter les clients en excel
     */
    public function export(Request $request)
    {
        $filename = 'client-file-' . now()->format('dmY') . '.xlsx';

        $clients = $request->input('all')
            ? Client::query()
            : client_filter(
                ClientFilterData::fromRequest($request)
            )->toQuery();

        Excel::store(new ClientsExport($clients), $filename, 'exportclient');

        return response()->json([
            'url' => Storage::disk('exportclient')->url($filename)
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function searchDuplicates(Request $request)
    {
        $request->validate([
            'nomcomplet' => 'required',
            "nom_cli" => 'required',
            "prenom_cli" => 'nullable',
            'date_naiss_cli' => 'required',
            'sexe_id' => 'required',
            'client_id' => ['nullable', 'exists:clients,id'],
        ]);

        $clients = Client::with(['sexe:id,description_sex'])
            ->where(function (Builder $query) use ($request) {
                $query->whereNomcompletClient($request->get('nomcomplet'))
                    ->orWhere(function (Builder $query) use ($request) {
                        $query->whereNomCli($request->get('nom_cli'))
                            ->wherePrenomCli($request->get('prenom_cli'));
                    });
            })
            ->whereDateNaissCli($request->get('date_naiss_cli'))
            ->whereSexeId($request->get('sexe_id'))
            ->when($request->client_id, function ($query) use ($request) {
                $query->where('id', '!=', $request->client_id);
            })
            ->get();

        return response()->json([
            'duplicates' => $clients
        ], $clients->isEmpty() ? Response::HTTP_OK : Response::HTTP_CONFLICT);
    }

    /**
     * @return JsonResponse
     *
     * @permission ClientController::printFidelityCard
     * @permission_desc Imprimer une carte de fidélité pour un client
     */
    public function printFidelityCard(Client $client, Request $request)
    {
        $fidelityCard = $client->fidelityCard()->latest()->first();

        if ($fidelityCard) {
            $path = $fidelityCard->path;
        } else {
            $centre = $client->user->centres()->first();
            $media = $centre?->medias()->whereName('logo')->first();

            $data = [
                'validity' => intval($request->input('validity')),
                'client' => $client,
                'centre' => $centre,
                'logo' => $media ?  base64_encode(file_get_contents('storage/' . $media->path . '/' . $media->filename)) : '',
                'mimetype' => $media ? $media->mimetype : '',
            ];

            try {
                save_browser_shot_pdf(
                    view: 'pdfs.fidelity-cart',
                    data: $data,
                    folderPath: 'storage/fidelity-card',
                    path: 'storage/fidelity-card/' . $client->ref_cli . '.pdf',
                    format: 'a6',
                    direction: 'landscape'
                );
            } catch (CouldNotTakeBrowsershot | Throwable $e) {
                Log::error($e->getMessage());

                return \response()->json([
                    'message' => __("Un erreur inattendue est survenu.")
                ], 400);
            }

            $path = 'fidelity-card/' . $client->ref_cli . '.pdf';
            $fileName = $client->ref_cli . '.pdf';


            $client->fidelityCard()->create([
                'name' => "fidelityCard",
                'disk' => 'public',
                'path' => $path,
                'filename' => $fileName,
                'mimetype' => 'pdf',
                'extension' => 'pdf',
                'validity' => $data['validity']
            ]);
        }

        return \response()->json([
            'url' => Storage::disk('public')->url($path)
        ]);
    }


    /**
     * @return JsonResponse
     *
     * @permission ClientController::getDuplicates
     * @permission_desc Afficher la liste des clients en doublons
     */
    public function getDuplicates(Request $request)
    {
        $clients = Client::with(['societe', 'prefix', 'typeDocument','createByCli','updateByCli','sexe','statusFamiliale'])
            ->get();

        if ($clients->isEmpty()) {
            return response()->json(['data' => [], 'total_groupes' => 0]);
        }

        $items = $clients->map(function ($c) {
            return [
                'model'          => $c,
                'key_nomcomplet' => $this->normalizeName($c->nomcomplet_client),
                'key_nom_prenom' => $this->normalizeName(
                    $c->nom_cli . ' ' . $c->prenom_cli . ' ' . $c->secondprenom_cli
                ),
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
        ];

        foreach ($items as $i => $item) {
            if ($item['key_nomcomplet'] !== '') {
                $buckets['nomcomplet'][$item['key_nomcomplet']][] = $i;
            }
            if ($item['key_nom_prenom'] !== '') {
                $buckets['nom_prenom'][$item['key_nom_prenom']][] = $i;
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
            ->map(function ($group) {
                return collect($group)->sortByDesc('created_at')->values();
            });

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

    /**
     * @return JsonResponse
     *
     * @permission ClientController::mergeDuplicates
     * @permission_desc Fusionner les fiches de clients en doublons
     */
    public function mergeDuplicates(Request $request)
    {
        $request->validate([
            'master_id' => 'required|exists:clients,id',
            'duplicate_ids' => 'required|array',
            'duplicate_ids.*' => 'exists:clients,id|different:master_id',
        ]);

        $masterId = $request->input('master_id');
        $duplicateIds = $request->input('duplicate_ids');

        DB::beginTransaction();

        try {
            $masterClient = Client::findOrFail($masterId);

            Prestation::whereIn('client_id', $duplicateIds)
                ->update(['client_id' => $masterId]);

            PriseEnCharge::whereIn('client_id', $duplicateIds)
                ->update(['client_id' => $masterId]);

            CampagneFacture::whereIn('patient_id', $duplicateIds)
                ->update(['patient_id' => $masterId]);

            ConventionAssocie::whereIn('client_id', $duplicateIds)
                ->update(['client_id' => $masterId]);

            PatientArchive::whereIn('patient_id', $duplicateIds)
                ->update(['patient_id' => $masterId]);

            Proforma::whereIn('client_id', $duplicateIds)
                ->update(['client_id' => $masterId]);

            RendezVous::whereIn('client_id', $duplicateIds)
                ->update(['client_id' => $masterId]);

            ResultatExamenCampagneFacture::whereIn('patient_id', $duplicateIds)
                ->update(['patient_id' => $masterId]);

            Client::whereIn('id', $duplicateIds)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Fusion des fiches et réassignation de toutes les données associées effectuées avec succès.',
                'master_id' => $masterId
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la fusion : ' . $e->getMessage()
            ], 500);
        }
    }
}
