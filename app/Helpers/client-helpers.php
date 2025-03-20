<?php

use App\DTO\ClientFilterData;
use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use LaravelIdea\Helper\App\Models\_IH_Client_C;

if (! function_exists('client_filter')) {
    function client_filter(ClientFilterData $filterData): LengthAwarePaginator|_IH_Client_C|array
    {
        $search = $filterData->search;
        $perPage = $filterData->perPage;
        $page = $filterData->page;

        return Client::with('user', 'societe', 'prefix', 'typeDocument', 'sexe', 'statusFamiliale', 'createByCli', 'updateByCli')
            ->when($search, function (Builder $query) use ($search) {
                $query->whereLike('nom_cli', "%{$search}%")
                    ->orWhereLike('nomcomplet_client', "%{$search}%")
                    ->orWhereLike('prenom_cli', "%{$search}%")
                    ->orWhereLike('secondprenom_cli', "%{$search}%")
                    ->orWhereLike('ref_cli', "%{$search}%")
                    ->orWhereLike('email_cli', "%{$search}%")
                    ->orWhereLike('tel_cli', "%{$search}%")
                    ->orWhereLike('tel2_cli', "%{$search}%");
            })
            ->when($filterData->sexe, function (Builder $query) use ($filterData) {
                $query->where('sexe_id', $filterData->sexe);
            })
            ->when($filterData->status_familiale, function (Builder $query) use ($filterData) {
                $query->where('status_familiale_id', $filterData->status_familiale);
            })
            ->when($filterData->type_document, function (Builder $query) use ($filterData) {
                $query->where('type_document_id', $filterData->type_document);
            })
            ->when($filterData->type_cli, function (Builder $query) use ($filterData) {
                $query->where('type_cli', $filterData->type_cli);
            })
            ->when($filterData->status_cli, function (Builder $query) use ($filterData) {
                $query->where('status_cli', $filterData->status_cli);
            })
            ->when($filterData->sort_colonne && $filterData->sort_direction, function (Builder $query) use ($filterData) {
                $query->orderBy($filterData->sort_colonne, $filterData->sort_direction);
            },
                function (Builder $query) {
                    $query->orderBy('created_at', 'desc');
                })
            ->paginate(perPage: $perPage, page: $page);
    }
}
