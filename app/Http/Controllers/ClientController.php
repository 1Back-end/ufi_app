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
        $centres = Centre::select('nom_centre', 'id')->get()->toArray();

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
     */
    public function store(ClientRequest $request)
    {
        $dataValidated = $request->validated();
        $authUser = User::first(); // auth()->user();

        $dataValidated['enfant_cli'] = Carbon::parse($dataValidated['date_naiss_cli'])->age <= 14;
        $dataValidated['date_naiss_cli'] = $dataValidated['date_naiss_cli_estime']
            ?  now()->subYears($dataValidated['age'])->year .'-01-01'
            : $dataValidated['date_naiss_cli'];
        $dataValidated['create_by_cli'] = $authUser->id; //auth()->user()->id;
        $dataValidated['updated_by_cli'] = $authUser->id; //auth()->user()->id;
        $dataValidated['user_id'] = $authUser->id; // Add User for this client

        unset($dataValidated['age']);

        $client = Client::create($dataValidated);

        $refcli = now()->year . now()->month . $client->id . $dataValidated['site_id']; // Todo: C'est quoi le code du site

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
     */
    public function show(Client $client)
    {
        // Return the client with its relations
        return response()->json($client->load('user', 'societe', 'prefix', 'typeDocument', 'sexe', 'statusFamiliale', 'createByCli', 'updateByCli'), Response::HTTP_OK);
    }

    public function update(ClientRequest $request, Client $client)
    {
        $dataValidated = $request->validated();
        $authUser = User::first(); // auth()->user();

        $dataValidated['enfant_cli'] = Carbon::parse($dataValidated['date_naiss_cli'])->age <= 14;
        $dataValidated['date_naiss_cli'] = $dataValidated['date_naiss_cli_estime']
            ?  now()->subYears($dataValidated['age'])->year .'-01-01'
            : $dataValidated['date_naiss_cli'];
        $dataValidated['create_by_cli'] = $authUser->id; //auth()->user()->id;
        $dataValidated['updated_by_cli'] = $authUser->id; //auth()->user()->id;
        $dataValidated['user_id'] = $authUser->id; // Add User for this client

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

    public function printFidelityCard()
    {
        // Todo: Renvoyer un PDF de carte de fidelité avec un QR Code
    }
}
