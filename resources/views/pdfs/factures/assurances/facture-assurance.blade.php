@extends('pdfs.layouts.template')

@section('content')
    <div class="row">
        <div class="col-4"></div>
        <div class="text-dark mt-2 text-center col">
            <p>{{ $centre->town }}, le {{ now()->translatedFormat('j F Y') }}</p>

            <div class="mt-2">
                <p class="my-0">A</p>

                <p class="my-0">L'attention de Monsieur le Directeur Général</p>
                <p>{{ $assurance->nom }}</p>
            </div>
        </div>
    </div>

    <p class="text-center w-100 text-decoration-underline">
        FACTURE N° {{ $code }}
    </p>

    <div class="">
        <span class="text-decoration-underline">Objet:</span>
        <span>Analyse médicale de vos assurés</span>

        <p>Période: du {{ $start_date->translatedFormat('j F Y') }} au {{ $end_date->translatedFormat('j F Y') }}</p>
    </div>

    @php
        $i = 1;
    @endphp

    <div class="d-flex justify-content-center mt-2">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>N° Facture</th>
                    <th>Date</th>
                    <th>Nom du patient</th>
                    <th class="text-center">Montant Réclamé</th>
                    <th class="text-center">Modérateur</th>
                    <th class="text-center">Montant à réglé</th>
                </tr>
            </thead>
            <tbody>
                @foreach($prestations as $prestation)
                    @foreach($prestation->factures as $facture)
                        <tr>
                            <td>
                                {{ $i++ }}
                            </td>
                            <td>{{ $facture->code }}</td>
                            <td>{{ $facture->date_fact->format('d/m/Y') }}</td>
                            <td>{{ $prestation->client->nomcomplet_client }}</td>
                            <td class="text-center">
                                {{ \App\Helpers\FormatPrice::format($facture->amount) }}
                            </td>
                            <td class="text-center">
                                {{ \App\Helpers\FormatPrice::format($facture->amount_client) }}
                            </td>
                            <td class="text-center">
                                {{ \App\Helpers\FormatPrice::format($facture->amount_pc) }}
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
