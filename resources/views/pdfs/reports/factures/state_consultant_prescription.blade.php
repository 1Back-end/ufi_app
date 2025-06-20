@extends('pdfs.layouts.template')

@section('content')

    <h1 class="fs-3 fw-bold text-center text-uppercase">
        ETAT DES PRESTATIONS par consultant
    </h1>

    <p class="fst-italic text-end">Date d'impression: {{ now()->format('d/m/Y H:i') }}</p>

    <h2 class="fw-bold text-center fs-5">
        {{ $centre->name }} Période {{ $start_date->format('d/m/Y') }} au {{ $end_date->format('d/m/Y') }}
    </h2>

    <table class="table border-0 mt-2">
        <thead>
            <tr class="border border-1 border-black">
                <th>N° Facture</th>
                <th>Nom du patient</th>
                <th>Eléments</th>
                <th>PC</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            @foreach($consultants as $consultant)
                @php
                    $amount = 0;
                @endphp

                <tr>
                    <td class="border-0">Consultant: <strong>{{ $consultant[0]->consultant?->nomcomplet }}</strong></td>
                    <td colspan="4" class="border-0"></td>
                </tr>

                @foreach($consultant as $prestation)
                    <tr>
                        <td class="border-0">{{ $prestation->factures[0]->code }}</td>
                        <td class="border-0">{{ $prestation->client->nomcomplet_client }}</td>
                        <td class="border-0">{{ $prestation->type_label }}</td>
                        <td class="border-0">{{ $prestation->prise_charge_id ? 'Oui' : 'Non' }}</td>
                        <td class="border-0">{{ \App\Helpers\FormatPrice::format($prestation->factures[0]->amount) }}</td>
                    </tr>

                    @php
                        $amount += $prestation->factures[0]->amount;
                    @endphp
                @endforeach
                <tr class="mt-2">
                    <td colspan="4" class="border-0"></td>
                    <td class="border-top border-bottom border-black">{{ \App\Helpers\FormatPrice::format($amount) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

@endsection
