@extends('pdfs.layouts.template')

@section('content')

    <h1 class="fs-3 fw-bold text-center text-uppercase">
        ETAT @if ($start_date->isCurrentDay() && $end_date->isCurrentDay()) journalier @endif DES REGLEMENTS CLIENTS
    </h1>

    <p class="fst-italic text-end">Date d'impression: {{ now()->format('d/m/Y H:i') }}</p>

    <h2 class="fw-bold text-center fs-5">
        {{ $centre->name }} Période {{ $start_date->format('d/m/Y') }} au {{ $end_date->format('d/m/Y') }}
    </h2>

    <table class="table table-bordered border-black">
        <thead>
            <tr>
                @if($rapprochement)
                    <th>N° Facture</th>
                    <th>Nom patient</th>
                    <th>Prescripteur</th>
                    <th>Elements</th>
                    <th>Pris en charge</th>
                    <th>Proforma</th>
                    <th>Montant total</th>
                    <th>Part patient</th>
                    <th>Montant paye patient</th>
                    <th>Montant pris en charge</th>
                    <th>Assurance</th>
                    <th>Creation DT</th>
                @else
                    <th>N° Facture</th>
                    <th>Date facture</th>
                    <th>Mode de règlement</th>
                    <th>Nom patient</th>
                    <th>Montant Total</th>
                    <th>Montant réglé</th>
                    <th>Remise</th>
                    <th>Reste à payer</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @if($rapprochement)
                @foreach($prestations as $prestation)
                    <tr>
                        <td>{{ $prestation->factures->first() ? $prestation->factures->first()->code : "Facture non crée" }}</td>
                        <td>{{ $prestation->client->nomcomplet_client }}</td>
                        <td>{{ $prestation?->consultant?->nomcomplet }}</td>
                        <td>
                            <ul class="list-unstyled">
                                @foreach($prestation->actes as $acte)
                                    <li>- {{ $acte->name }}</li>
                                @endforeach

                                @foreach($prestation->soins as $soin)
                                    <li>- {{ $soin->name }}</li>
                                @endforeach

                                @foreach($prestation->consultations as $consultation)
                                    <li>- {{ $consultation->name }}</li>
                                @endforeach

                                @foreach($prestation->hospitalisations as $hospitalisation)
                                    <li>- {{ $hospitalisation->name }}</li>
                                @endforeach

                                @foreach($prestation->products as $product)
                                    <li>- {{ $product->name }}</li>
                                @endforeach

                                @foreach($prestation->examens as $examen)
                                    <li>- {{ $examen->name }}</li>
                                @endforeach
                            </ul>
                        </td>
                        <td>
                            {{ $prestation->priseCharge ? 'OUI' : 'NON' }}
                        </td>
                        <td>
                            FAUX
                        </td>
                        <td>
                            {{ \App\Helpers\FormatPrice::format($prestation->factures->first()?->amount) }}
                        </td>
                        <td>{{ \App\Helpers\FormatPrice::format($prestation->factures->first()?->amount_client) }}</td>
                        <td>{{ \App\Helpers\FormatPrice::format($prestation->factures->first()?->regulations_total_except_particular) }}</td>
                        <td>{{ \App\Helpers\FormatPrice::format($prestation->factures->first()?->amount_pc) }}</td>
                        <td>{{ $prestation->priseCharge?->assureur->nom }}</td>
                        <td>{{ $prestation->factures->first()?->date_fact->format("d/m/Y H:i") }}</td>
                    </tr>
                @endforeach
            @else
                @foreach($prestations->sortBy(fn($value, $key) => $value->factures[0]->date_fact) as $prestation)
                    <tr>
                        <td>{{ $prestation->factures[0]->code }}</td>
                        <td>{{ $prestation->factures[0]->date_fact->format("d/m/Y H:i") }}</td>
                        <td style="width: 20% !important;">
                            @if($prestation->factures[0]->regulations()->where('regulations.particular', false)->count())
                                <ul class="list-unstyled">
                                    @foreach($prestation->factures[0]->regulations as $regulation)
                                        @if(!$regulation->particular)
                                            <li>{{ $regulation->regulationMethod->name }}</li>
                                        @endif
                                    @endforeach
                                </ul>
                            @endif

                            @if($prestation->payableBy)
                                {{ $prestation->payableBy->nomcomplet_client }}
                            @endif
                        </td>
                        <td style="width: 30%">{{ $prestation->client->nomcomplet_client }}</td>
                        <td>{{ \App\Helpers\FormatPrice::format($prestation->factures[0]->amount) }}</td>
                        <td>
                            @if ($prestation->factures[0]->regulations->where('particular', false)->count() > 0)
                                <ul class="list-unstyled">
                                    @foreach($prestation->factures[0]->regulations as $regulation)
                                        @if(!$regulation->particular)
                                            <li>
                                                <strong>{{ $regulation->regulationMethod->name }}: </strong> {{ \App\Helpers\FormatPrice::format($regulation->amount) }}
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            @endif

                            <span class="d-flex gap-2">
                                <span>Total: </span>

                                @if ($prestation->factures[0]->regulations_total_except_particular)
                                    {{ \App\Helpers\FormatPrice::format($prestation->factures[0]->regulations->sum('amount')) }}
                                @else
                                    @if ($prestation->payable_by && $prestation->factures[0]->state->value === \App\Enums\StateFacture::PAID->value)
                                        {{ \App\Helpers\FormatPrice::format($prestation->factures[0]->amount_client) }}
                                    @else
                                        0
                                    @endif
                                @endif
                            </span>
                        </td>
                        <td>{{ \App\Helpers\FormatPrice::format($prestation->factures[0]->amount_remise) }}</td>
                        <td>{{ \App\Helpers\FormatPrice::format($prestation->factures[0]->amount_rest) }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
        @if(!$rapprochement)
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="4" class="text-end">Totaux:</td>
                    <td>{{ \App\Helpers\FormatPrice::format($amounts['total']) }}</td>
                    <td>
                        Total: {{ \App\Helpers\FormatPrice::format($amounts['amount_total_regulation']) }}
                    </td>
                    <td>{{ \App\Helpers\FormatPrice::format($amounts['total_remise']) }}</td>
                    <td>{{ \App\Helpers\FormatPrice::format($amounts['amount_rest']) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    @if(!$rapprochement)
        <h2 class="text-center fw-bold text-uppercase fs-6">Montant des règlements par mode de règlement: </h2>
        <div class="d-flex gap-4 justify-content-center">
            @foreach($amounts['amount_per_method'] as $method => $amount)
                <div class="d-flex justify-content-center">
                    <div class="d-flex gap-3 align-items-center">
                        <div class="">
                            {{ $method }}:
                        </div>
                        <div class="fw-bold fs-5">
                            {{ \App\Helpers\FormatPrice::format($amount) }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <p class="text-end d-flex align-items-center gap-5">
        <span></span>
    </p>

@endsection
