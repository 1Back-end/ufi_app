<?php

namespace App\Http\Controllers;

use App\Enums\StatusClient;
use App\Exports\ClientsExport;
use App\Http\Requests\ClientRequest;
use App\Models\Client;
use App\Models\Prefix;
use App\Models\Sexe;
use App\Models\Societe;
use App\Models\StatusFamiliale;
use App\Models\TypeDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $societes = Societe::pluck('nom_soc_cli', 'id')->toArray();
        $typeDocuments = TypeDocument::pluck('description_typedoc', 'id')->toArray();
        $prefixes = Prefix::pluck('prefixe', 'id')->toArray();
        $statusFamiliales = StatusFamiliale::pluck('description_statusfam', 'id')->toArray();
        $sexes = Sexe::pluck('description_sex', 'id')->toArray();

        // Return the data as a JSON response
        return response()->json([
            'societes' => $societes,
            'typeDocuments' => $typeDocuments,
            'prefixes' => $prefixes,
            'statusFamiliales' => $statusFamiliales,
            'sexes' => $sexes,
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
        $search = $request->input('search');
        $perPage = $request->input('perPage', 10);
        $page = $request->input('page', 1);

        // Search clients by name, first name, second name, reference, email and phone numbers.
        $clients = Client::with('user', 'societe', 'prefix', 'typeDocument', 'sexe', 'statusFamiliale', 'createByCli', 'updateByCli')
            ->when($search, function (Builder $query) use ($search) {
                $query->whereLike('nom_cli', "%{$search}%")
                    ->orWhereLike('prenom_cli', "%{$search}%")
                    ->orWhereLike('secondprenom_cli', "%{$search}%")
                    ->orWhereLike('ref_cli', "%{$search}%")
                    ->orWhereLike('email_cli', "%{$search}%")
                    ->orWhereLike('tel_cli', "%{$search}%")
                    ->orWhereLike('tel2_cli', "%{$search}%");
            })->paginate(perPage: $perPage, page: $page);

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
        $client = Client::create($request->validated());

        // Format the reference of the client
        // The reference is composed of the year, month, id and the code of the site
        $refcli = now()->year . now()->month . $client->id . 'Code site'; // Todo: C'est quoi le code du site

        // Update the client with the new reference
        $client->update(['ref_cli' => $refcli]);

        return response()->json([
            'message' => 'Client a été créé avec succès !',
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Client  $client
     * @return JsonResponse
     */
    public function show(Client $client)
    {
        // Return the client with its relations
        return response()->json($client->load('user', 'societe', 'prefix', 'typeDocument', 'sexe', 'statusFamiliale', 'createByCli', 'updateByCli'), Response::HTTP_OK);
    }

    public function update(ClientRequest $request, Client $client)
    {
        $client->update($request->validated());

        return response()->json([
            'message' => 'Client a été mis à jour avec succès !',
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * Supprime un client
     *
     * @param \App\Models\Client $client
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
    public function export()
    {
        // Todo: Exporter les clients en Excel
        // Todo: le but est de sortir le fichier Excel et de renvoyer un public vers celui-ci afin qu'on puisse le télécharger dans le navigateur

        Excel::store(new ClientsExport(), 'client-file.xlsx', 'exportclient');

        return Storage::disk('exportclient')->url('client-file.xlsx');
    }

    public function printFidelityCard()
    {
        // Todo: Renvoyer un PDF de carte de fidelité avec un QR Code
    }
}
