<?php

namespace App\Http\Controllers;

use App\DTO\ClientFilterData;
use App\Enums\StatusClient;
use App\Exports\ClientsExport;
use App\Http\Requests\ClientRequest;
use App\Models\Centre;
use App\Models\Client;
use App\Models\Prefix;
use App\Models\Sexe;
use App\Models\Societe;
use App\Models\StatusFamiliale;
use App\Models\TypeDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Enum;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception;
use Symfony\Component\HttpFoundation\Response;

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
        // Search clients by name, first name, second name, reference, email and phone numbers.
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
        $dataValidated = $request->validated();

        $dataValidated['enfant_cli'] = Carbon::parse($dataValidated['date_naiss_cli'])->age <= 14;
        $dataValidated['date_naiss_cli'] = $dataValidated['date_naiss_cli_estime']
            ? now()->subYears($dataValidated['age'])->year . '-01-01'
            : $dataValidated['date_naiss_cli'];

        $dataValidated['site_id'] = 1;

        unset($dataValidated['age']);

        DB::beginTransaction();
        try {
            $client = Client::create($dataValidated);

            $refcli = now()->year . now()->month . $client->id . $dataValidated['site_id']; // Todo: C'est quoi le code du site
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Une erreur est survenue lors de la création du client',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        DB::commit();

        // Update the client with the new reference
        $client->update(['ref_cli' => $refcli]);

        return response()->json([
            'message' => 'Client a été créé avec succès !',
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

        $dataValidated['enfant_cli'] = Carbon::parse($dataValidated['date_naiss_cli'])->age <= 14;
        $dataValidated['date_naiss_cli'] = $dataValidated['date_naiss_cli_estime']
            ? now()->subYears($dataValidated['age'])->year . '-01-01'
            : $dataValidated['date_naiss_cli'];

        unset($dataValidated['age']);

        $client->update($dataValidated);

        return response()->json([
            'message' => 'Client a été mis à jour avec succès !',
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
        // Supprime le client
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
            'date_naiss_cli' => 'required',
            'sexe_id' => 'required',
        ]);

        $clients = Client::with(['sexe:id,description_sex'])
            ->whereNomcompletClient($request->get('nomcomplet'))
            ->whereDateNaissCli($request->get('date_naiss_cli'))
            ->whereSexeId($request->get('sexe_id'))
            ->get();

        return response()->json([
            'duplicates' => $clients
        ], $clients->isEmpty() ? Response::HTTP_OK : Response::HTTP_CONFLICT);
    }

    /**
     * @return void
     *
     * @permission ClientController::printFidelityCard
     * @permission_desc Imprimer une carte de fidélité pour un client
     */
    public function printFidelityCard()
    {
        // Todo: Renvoyer un PDF de carte de fidelité avec un QR Code
    }
}
