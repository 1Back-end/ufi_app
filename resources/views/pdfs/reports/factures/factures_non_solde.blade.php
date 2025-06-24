@php use App\Helpers\FormatPrice; @endphp
@extends('pdfs.layouts.template')

@section('content')

    <h1 class="fs-3 fw-bold text-center text-uppercase">
        ETAT DES factures non soldées
    </h1>

    <p class="fst-italic text-end">Date d'impression: {{ now()->format('d/m/Y H:i') }}</p>

    <h2 class="fw-bold text-center fs-5">
        {{ $centre->name }} Période {{ $start_date->format('d/m/Y') }} au {{ $end_date->format('d/m/Y') }}
    </h2>

    <table class="table table-bordered border-0 mt-2">
        <thead>
            <tr class="border border-1 border-black">
                <th>N° Facture</th>
                <th>PC</th>
                <th>Nom du patient</th>
                <th>Net à percevoir</th>
                <th>Remise</th>
                <th>Montant Client</th>
                <th>Montant perçu</th>
                <th>Reste à payer</th>
            </tr>
        </thead>
        <tbody>
            @php
                $amount = 0;
                $amount_remise = 0;
                $amount_client = 0;
                $amount_regulation = 0;
                $amount_reste = 0;
            @endphp

            @foreach($dateFactures as $date => $dateFacture)
                <tr class="border-0">
                    <td colspan="8" class="border-0"></td>
                </tr>

                <tr>
                    <td colspan="3" class="border-0 fs-4" style="background-color: #e1e1e1">
                        Date de la facture: <strong>{{ $date }}</strong>
                    </td>
                    <td colspan="5" class="border-0"></td>
                </tr>

                @php
                    $amount_fact = 0;
                    $amount_remise_fact = 0;
                    $amount_client_fact = 0;
                    $amount_regulation_fact = 0;
                    $amount_reste_fact = 0;
                @endphp

                @foreach($dateFacture as $facture)
                    <tr>
                        <td>{{ $facture->code }}</td>
                        <td>{{ $facture->amount_pc ? 'OUI' : 'NON' }}</td>
                        <td>{{ $facture->prestation->client->nomcomplet_client }}</td>
                        <td>{{ FormatPrice::format($facture->amount) }}</td>
                        <td>{{ FormatPrice::format($facture->amount_remise) }}</td>
                        <td>{{ FormatPrice::format($facture->amount_client) }}</td>
                        <td>{{ FormatPrice::format($facture->regulations->sum('amount')) }}</td>
                        <td>{{ FormatPrice::format($facture->amount_client - $facture->regulations->sum('amount')) }}</td>
                    </tr>

                    @php
                        $amount += $facture->amount;
                        $amount_remise += $facture->amount_remise;
                        $amount_client += $facture->amount_client;
                        $amount_regulation += $facture->regulations->sum('amount');
                        $amount_reste += $facture->amount_client - $facture->regulations->sum('amount');

                        $amount_fact += $facture->amount;
                        $amount_remise_fact += $facture->amount_remise;
                        $amount_client_fact += $facture->amount_client;
                        $amount_regulation_fact += $facture->regulations->sum('amount');
                        $amount_reste_fact += $facture->amount_client - $facture->regulations->sum('amount');
                    @endphp
                @endforeach

                <tr class="mt-2 border-bottom-0">
                    <td colspan="3" class="fs-5 fw-bold text-end border-0"></td>
                    <td class="border-bottom border-dark" style="background-color: #e1e1e1">{{ FormatPrice::format($amount_fact) }}</td>
                    <td class="border-bottom border-dark" style="background-color: #e1e1e1">{{ FormatPrice::format($amount_remise_fact) }}</td>
                    <td class="border-bottom border-dark" style="background-color: #e1e1e1">{{ FormatPrice::format($amount_client_fact) }}</td>
                    <td class="border-bottom border-dark" style="background-color: #e1e1e1">{{ FormatPrice::format($amount_regulation_fact) }}</td>
                    <td class="border-bottom border-dark" style="background-color: #e1e1e1">{{ FormatPrice::format($amount_reste_fact) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="border-0">
                <td colspan="8" class="border-0"></td>
            </tr>
            <tr class="mt-4 border-0 fw-bold">
                <td colspan="3" class="border-0 fw-light text-end">Totaux de la période:</td>
                <td class="border border-dark">{{ FormatPrice::format($amount) }}</td>
                <td class="border border-dark">{{ FormatPrice::format($amount_remise) }}</td>
                <td class="border border-dark">{{ FormatPrice::format($amount_client) }}</td>
                <td class="border border-dark">{{ FormatPrice::format($amount_regulation) }}</td>
                <td class="border border-dark">{{ FormatPrice::format($amount_reste) }}</td>
            </tr>
        </tfoot>
    </table>

@endsection
