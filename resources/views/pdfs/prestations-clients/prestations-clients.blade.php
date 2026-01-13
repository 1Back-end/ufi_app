@php use Carbon\Carbon; @endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>ETATS JOURNALIERS DES CLIENTS</title>

    <style>
        {!! $bootstrap !!}
    </style>

    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
            counter-reset: page;
        }

        body, html {
            height: 100%;
            margin: 0;
            padding: 0;
            font-size: 3mm !important;
            font-family: "Times New Roman", serif;
        }

        .print-wrapper {
            position: relative;
        }

        .print-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 10mm;
            text-align: center;
        }

        .page-number:before {
            content: "Page " counter(page) " / " counter(pages);
        }

        h1 {
            font-size: 5mm !important;
        }

        table {
            page-break-inside: auto;
            width: 100%;
        }

        thead {
            display: table-header-group; /* Garde l'en-tête sur chaque page */
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        img {
            width: auto;
            height: auto;
        }
    </style>

</head>
<body>

<div class="col-lg-12 col-sm-12 p-0 print-wrapper">


    <header class="d-flex align-items-center size" style="font-family: 'Times New Roman', serif">
        <div class="w-25">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path($logo))) }}" alt=""
                 class="img-fluid w-50">
        </div>

        <div class="text-center" style="line-height: 18px">
            <div class="fs-3 text-uppercase fw-bold">
                {{ $centre->name }}
            </div>

            <div class="">
                - {{ $centre->address }} - {{ $centre->town }}
            </div>

            <div class="">
                BP: {{ $centre->postal_code }} {{ $centre->town }} -
                Tél. {{ $centre->tel }} {{ $centre->tel2 ? '/' . $centre->tel2 : '' }}
                / Fax: {{ $centre->fax ?? '' }}
            </div>

            <div class="">
                Email: {{ $centre->email }}
            </div>

            <div class="">
                Autorisation n° {{ $centre->autorisation }}
                NIU: {{ $centre->contribuable }}
            </div>
        </div>
    </header>


    <div class="mt-2 w-100" style="border-top: 1px double rgb(0, 0, 0, 0.75); margin-bottom: 2px"></div>
    <div class="mb-2 w-100" style="border-top: 1px double rgb(0, 0, 0, 0.75);"></div>


    <h1 class="fs-3 fw-bold text-center text-uppercase">
        ETATS DES JOURNALIERS CLIENTS
    </h1>

    <p class="fst-italic text-end">Date d'impression: {{ now()->format('d/m/Y H:i') }}</p>

    <h2 class="fw-bold text-center fs-5 text-uppercase">
        {{ $centre->name }} - {{ $titre }}
    </h2>


    <div class="mt-2 w-100">
        <table class="table table-bordered table-striped text-center" style="font-size: 12px;">
            <thead>
            <tr>
                <th>N° Facture</th>
                <th>Date facture</th>
                <th>Mode de règlement</th>
                <th>PC/Associé</th>
                <th>Nom patient</th>
                <th>Montant Total</th>
                <th>Montant PC</th>
                <th>Montant Remise</th>
                <th>Montant à payer client</th>
                <th>Montant Encaissé</th>
                @php
                    $modeSelectionne = request('mode_reglement');
                @endphp
                @if(!$modeSelectionne)
                    <th>Reste à payer client</th>
                @endif
            </tr>
            </thead>
            <tbody>
            @foreach ($prestations as $index => $prestation)
                @php
                    $facture = $prestation->factures->first();
                @endphp
                <tr>
                    <td>{{ $facture ? $facture->code : "Facture non créée" }}</td>
                    <td>{{ $prestation->created_at?->format('d/m/Y') }}</td>

                    @php
                        $modeSelectionne = request('mode_reglement');
                    @endphp

                    <td style="width: 20% !important;">
                        @if($facture && $facture->regulations->where('particular', false)->count())
                            <ul class="list-unstyled">
                                @foreach($facture->regulations->where('particular', false) as $regulation)
                                    @if(!$modeSelectionne || $regulation->regulation_method_id == $modeSelectionne)
                                        <li>
                                            {{ optional($regulation->regulationMethod)->name }}
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        @endif
                    </td>

                    <td>
                        @if($prestation->payableBy)
                            {{ $prestation->payableBy->nomcomplet_client }}
                        @endif

                        @if($prestation->priseCharge)
                            {{ optional($prestation->priseCharge->assureur)->nom }}
                        @endif
                    </td>

                    <td>{{ optional($prestation->client)->nomcomplet_client }}</td>

                    <td>{{ \App\Helpers\FormatPrice::format(optional($facture)->amount) }}</td>
                    <td>{{ \App\Helpers\FormatPrice::format(optional($facture)->amount_pc) }}</td>
                    <td>{{ \App\Helpers\FormatPrice::format(optional($facture)->amount_remise) }}</td>
                    <td>{{ \App\Helpers\FormatPrice::format(optional($facture)->amount_client) }}</td>

                    @php
                        use Carbon\Carbon;

                        $modeSelectionne = request('mode_reglement');

                        $factureStart = request('facture_start')
                            ? Carbon::parse(request('facture_start'))->startOfDay()
                            : null;

                        $factureEnd = request('facture_end')
                            ? Carbon::parse(request('facture_end'))->endOfDay()
                            : null;

                        // Toutes les factures (sécurisé)
                        $allFactures = $prestations->flatMap->factures->filter();

                        // Tous les règlements globaux (historique complet)
                        $allRegulationsGlobal = $allFactures
                            ->flatMap->regulations
                            ->where('particular', false);

                        // Règlements FILTRÉS (pour affichage)
                        $allRegulationsFiltered = $allRegulationsGlobal
                            ->when($modeSelectionne, fn ($c) =>
                                $c->where('regulation_method_id', $modeSelectionne)
                            )
                            ->when($factureStart && $factureEnd, fn ($c) =>
                                $c->filter(fn ($r) =>
                                    Carbon::parse($r->date)->between($factureStart, $factureEnd)
                                )
                            );

                        // TOTAUX FACTURES (GLOBAL)
                        $totalMontant = $allFactures->sum('amount');
                        $totalPc      = $allFactures->sum('amount_pc');
                        $totalRemise  = $allFactures->sum('amount_remise');
                        $totalClient  = $allFactures->sum('amount_client');

                        // ENCAISSÉ
                        $totalEncaisseGlobal = $allRegulationsGlobal->sum('amount');
                        $totalEncaisseFiltre = $allRegulationsFiltered->sum('amount');

                        // RESTE À PAYER (RÉEL)
                        $resteAPayer = max(0, $totalClient - $totalEncaisseGlobal);
                    @endphp

                    @php
                        $regulationsFacture = $facture
                            ? $allRegulationsFiltered->where('facture_id', $facture->id)
                            : collect();

                        $totalPaidFactureGlobal = $allRegulationsGlobal
                            ->where('facture_id', optional($facture)->id)
                            ->sum('amount');

                        $resteFacture = max(
                            optional($facture)->amount_client - $totalPaidFactureGlobal,
                            0
                        );
                    @endphp

                    {{-- Montant encaissé --}}
                    <td>
                        @if($regulationsFacture->count())
                            <ul class="list-unstyled mb-0">
                                @foreach($regulationsFacture as $reg)
                                    <li>
                                        <strong>{{ optional($reg->regulationMethod)->name }} :</strong>
                                        {{ \App\Helpers\FormatPrice::format($reg->amount) }}
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            {{ \App\Helpers\FormatPrice::format(0) }}
                        @endif
                    </td>

                    @if(!$modeSelectionne)
                        <td>{{ \App\Helpers\FormatPrice::format($resteFacture) }}</td>
                    @endif

               </tr>
            @endforeach
            </tbody>


        </table>
        <div style="page-break-inside: avoid; margin-top: 10px;">
            <table class="table table-bordered table-striped text-center" style="font-size: 2.5mm;">
                <tr class="fw-bold">
                    <td colspan="5">Totaux</td>

                    <td>Montant Total: {{ \App\Helpers\FormatPrice::format($totalMontant) }}</td>
                    <td>Montant PC: {{ \App\Helpers\FormatPrice::format($totalPc) }}</td>
                    <td>Montant Remise: {{ \App\Helpers\FormatPrice::format($totalRemise) }}</td>
                    <td>Montant à payer: {{ \App\Helpers\FormatPrice::format($totalClient) }}</td>
                    <td>Montant encaissé: {{ \App\Helpers\FormatPrice::format($totalEncaisseFiltre) }}</td>

                    @if(!$modeSelectionne)
                        <td>Reste à payer: {{ \App\Helpers\FormatPrice::format($resteAPayer) }}</td>
                    @endif
                </tr>
            </table>

        </div>
        <br><br>
        @php
            $reglementsParMode = $allRegulationsFiltered
                ->groupBy(fn ($r) => optional($r->regulationMethod)->name ?? 'Inconnu')
                ->map(fn ($items) => $items->sum('amount'));
        @endphp


        <h2 class="text-center fw-bold text-uppercase fs-6">
            Montant des règlements par mode de règlement
        </h2>

        <div class="d-flex gap-4 justify-content-center">
            @forelse($reglementsParMode as $mode => $total)
                <div class="d-flex gap-3 align-items-center">
                    <div>{{ $mode }} :</div>
                    <div class="fw-bold fs-5">
                        {{ \App\Helpers\FormatPrice::format($total) }}
                    </div>
                </div>
            @empty
                <div class="text-muted">Aucun règlement</div>
            @endforelse
        </div>

    </div>


</body>
</html>
