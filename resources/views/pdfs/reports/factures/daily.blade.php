@extends('pdfs.layouts.template')

@section('content')

    <h1 class="fs-3 fw-bold text-center text-uppercase">
        ETATS @if ($start_date->isCurrentDay() && $end_date->isCurrentDay()) journalier @endif DES REGLEMENTS CLIENTS
    </h1>

    <p class="fst-italic text-end">Date d'impression: {{ now()->format('d/m/Y H:i') }}</p>

    <h2 class="fw-bold text-center fs-5">
        {{ $centre->name }} Période {{ $start_date->format('d/m/Y') }} au {{ $end_date->format('d/m/Y') }}
    </h2>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>N° Facture</th>
                <th>Date facture</th>
                <th>Mode de règlement</th>
                <th>Nom patient</th>
                <th>Montant Total</th>
                <th>Montant réglé</th>
                <th>Remise</th>
                <th>Montant PC</th>
            </tr>
        </thead>
        <tbody>
            @foreach($prestations as $prestation)
                <tr>
                    <td>{{ $prestation->factures[0]->code }}</td>
                    <td>{{ $prestation->factures[0]->date_fact->format("d/m/Y H:i") }}</td>
                    <td>
                        <ul class="list-unstyled">
                            @foreach($prestation->factures[0]->regulations as $regulation)
                                @if(! $regulation->particular)
                                    <li>{{ $regulation->regulationMethod->name }}</li>
                                @endif
                            @endforeach
                        </ul>
                    </td>
                    <td>{{ $prestation->client->nomcomplet_client }}</td>
                    <td>{{ \App\Helpers\FormatPrice::format($prestation->factures[0]->amount) }}</td>
                    <td>{{ \App\Helpers\FormatPrice::format($prestation->factures[0]->regulations_total_except_particular) }}</td>
                    <td>{{ \App\Helpers\FormatPrice::format($prestation->factures[0]->amount_remise) }}</td>
                    <td>{{ \App\Helpers\FormatPrice::format($prestation->factures[0]->amount_pc) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="fw-bold">
                <td colspan="4" class="text-end">Totaux:</td>
                <td>{{ \App\Helpers\FormatPrice::format($amounts[0]->total) }}</td>
                <td>{{ \App\Helpers\FormatPrice::format($amountTotalRegulation) }}</td>
                <td>{{ \App\Helpers\FormatPrice::format($amounts[0]->total_remise) }}</td>
                <td>{{ \App\Helpers\FormatPrice::format($amounts[0]->total_pc) }}</td>
            </tr>
        </tfoot>
    </table>

    <p class="text-end d-flex align-items-center gap-5">
        <span></span>
    </p>

@endsection
