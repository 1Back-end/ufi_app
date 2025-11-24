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
        <table class="table table-bordered table-striped text-center" style="font-size: 2.5mm;">
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
                    <td>{{ $facture ? $facture->date_fact?->format('d/m/Y') : $prestation->created_at?->format('d/m/Y') }}</td>

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
                        $totalPaid = $facture ? $facture->regulations->where('particular', false)->sum('amount') : 0;
                        $restAPayer = optional($facture)->amount_client - $totalPaid;
                    @endphp

                    @php
                        $modeSelectionne = request('mode_reglement'); // récupère le mode choisi
                    @endphp

                    <td>
                        @if($facture && $facture->regulations->where('particular', false)->count())
                            <ul class="list-unstyled">
                                @foreach($facture->regulations->where('particular', false) as $regulation)
                                    @if(!$modeSelectionne || $regulation->regulation_method_id == $modeSelectionne)
                                        <li>
                                            <strong>{{ optional($regulation->regulationMethod)->name }}:</strong>
                                            {{ \App\Helpers\FormatPrice::format($regulation->amount) }}
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        @endif
                    </td>

                    @if(!$modeSelectionne)
                        <td>{{ \App\Helpers\FormatPrice::format($restAPayer) }}</td>
                    @endif
                </tr>
            @endforeach
            </tbody>
            @php
                $modeSelectionne = request('mode_reglement');

                // Toutes les factures
                $allFactures = $prestations->flatMap->factures;

                // Filtrer les factures si un mode de règlement est sélectionné
                if($modeSelectionne) {
                    $allFactures = $allFactures->filter(function($facture) use ($modeSelectionne) {
                        return $facture->regulations->contains(fn($r) => !$r->particular && $r->regulation_method_id == $modeSelectionne);
                    });
                }

                // Régulations correspondantes
                $allRegulations = $allFactures->flatMap->regulations
                    ->where('particular', false)
                    ->when($modeSelectionne, fn($collection) => $collection->where('regulation_method_id', $modeSelectionne));
            @endphp
        </table>
        <div style="page-break-inside: avoid; margin-top: 10px;">
            <table class="table table-bordered table-striped text-center" style="font-size: 2.5mm;">
                <tr class="fw-bold">
                    <td colspan="5" class="text-center">Totaux: </td>
                    <td class="text-center">Montant Total: {{ \App\Helpers\FormatPrice::format($allFactures->sum('amount')) }}</td>
                    <td class="text-center">Montant PC: {{ \App\Helpers\FormatPrice::format($allFactures->sum('amount_pc')) }}</td>
                    <td class="text-center">Montant Remise: {{ \App\Helpers\FormatPrice::format($allFactures->sum('amount_remise')) }}</td>
                    <td class="text-center">Montant à payer: {{ \App\Helpers\FormatPrice::format($allFactures->sum('amount_client')) }}</td>
                    <td class="text-center">Montant Encaissé: {{ \App\Helpers\FormatPrice::format($allRegulations->sum('amount')) }}</td>
                    @if(!$modeSelectionne)
                        <td class="text-center">
                            Reste à payer: {{ \App\Helpers\FormatPrice::format($allFactures->sum('amount_client') - $allRegulations->sum('amount')) }}
                        </td>
                    @endif
                </tr>
            </table>
        </div>
    </div>
    <br><br>
    @php
        $modeSelectionne = request('mode_reglement'); // récupère le mode choisi

        $reglementsParMode = $prestations->flatMap->factures
            ->flatMap->regulations
            ->where('particular', false)
            // Filtrer si un mode de règlement a été sélectionné
            ->when($modeSelectionne, fn($collection) => $collection->where('regulation_method_id', $modeSelectionne))
            ->groupBy(fn($r) => optional($r->regulationMethod)->name)
            ->map(fn($items) => $items->sum('amount'));
    @endphp

    <h2 class="text-center fw-bold text-uppercase fs-6">Montant des règlements par mode de règlement:</h2>
    <div class="d-flex gap-4 justify-content-center">
        @foreach($reglementsParMode as $mode => $total)
            <div class="d-flex gap-3 align-items-center">
                <div>{{ $mode }}:</div>
                <div class="fw-bold fs-5">{{ \App\Helpers\FormatPrice::format($total) }}</div>
            </div>
        @endforeach
    </div>


</div>


</body>
</html>
