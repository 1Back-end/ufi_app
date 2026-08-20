<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>ETATS JOURNALIERS DES ASSURANCES</title>

    <style>
        {!! $bootstrap !!}
    </style>

    <style>
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
        ETATS DES JOURNALIERS DES PRESTATIONS
    </h1>

    <p class="fst-italic text-end">Date d'impression: {{ now()->format('d/m/Y H:i') }}</p>

    <h2 class="fw-bold text-center fs-5 text-uppercase">
        {{ $centre->name }} - {{ $titre }}
    </h2>


    <div class="mt-2 w-100">
        <table class="table table-bordered table-striped text-center border-black" style="font-size: 12px;">
            <thead>
            <tr>
                <th style="font-style: italic;font-size: 11px">N° Facture</th>
                <th style="font-style: italic;font-size: 11px">Création DT</th>
                <th style="font-style: italic;font-size: 11px">Nom patient</th>
                <th style="font-style: italic;font-size: 11px">Prescripteur</th>
                <th style="font-style: italic;font-size: 11px">Pris en charge</th>
                <th style="font-style: italic;font-size: 11px">Societé / Partenaire</th>
                <th style="font-style: italic;font-size: 11px">Montant Total</th>
                <th style="font-style: italic;font-size: 11px">Montant PC</th>
                <th style="font-style: italic;font-size: 11px">Part patient</th>
                <th style="font-style: italic;font-size: 11px">Montant payé patient</th>
                <th style="font-style: italic;font-size: 11px">Montant Remise</th>
                <th style="font-style: italic;font-size: 11px">Reste à payer client</th>
                <th style="font-style: italic;font-size: 11px">Assurance</th>
            </tr>
            </thead>
            <tbody>
            @php
                $sumAmount = 0;
                $sumAmountPc = 0;
                $sumAmountClient = 0;
                $sumRegulation = 0;
                $sumRemise = 0;
                $sumReste = 0;
            @endphp

            @foreach ($prestations as $index => $prestation)
                @php
                    $facture = $prestation->factures->first();
                    $factureReglee = $prestation->factures
                        ->first(fn($f) => $f->regulations && $f->regulations->where('state', 1)->isNotEmpty()) ?? $facture;
                    $regulationAmount = 0;
                    if ($factureReglee && $factureReglee->relationLoaded('regulations')) {
                        $regulationAmount = $factureReglee->regulations->where('state', 1)->sum('amount');
                    }
                    $montantClient = $facture ? ($facture->amount_client ?? 0) : 0;
                    $restAPayer = max(0, $montantClient - $regulationAmount);

                    // Cumul pour le récapitulatif
                    $sumAmount += $facture ? ($facture->amount ?? 0) : 0;
                    $sumAmountPc += $facture ? ($facture->amount_pc ?? 0) : 0;
                    $sumAmountClient += $montantClient;
                    $sumRegulation += $regulationAmount;
                    $sumRemise += $facture ? ($facture->amount_remise ?? 0) : 0;
                    $sumReste += $restAPayer;
                @endphp

                <tr>
                    <td>{{ $facture ? $facture->code : "Facture non créée" }}</td>
                    <td>{{ $prestation->created_at?->format('d/m/Y') }}</td>
                    <td>{{ optional($prestation->client)->nomcomplet_client }}</td>
                    <td>{{ $prestation?->consultant?->nomcomplet }}</td>

                    <td style="width: 10% !important;">
                        {{ $prestation->priseCharge ? 'OUI' : 'NON' }}
                    </td>

                    <td>
                        {{ optional($prestation->client)->societe?->nom_soc_cli }}
                    </td>

                    <td>{{ \App\Helpers\FormatPrice::format(optional($facture)->amount) }}</td>
                    <td>{{ \App\Helpers\FormatPrice::format(optional($facture)->amount_pc) }}</td>
                    <td>{{ \App\Helpers\FormatPrice::format(optional($facture)->amount_client) }}</td>
                    <td>{{ \App\Helpers\FormatPrice::format($regulationAmount) }}</td>
                    <td>{{ \App\Helpers\FormatPrice::format(optional($facture)->amount_remise) }}</td>
                    <td>{{ \App\Helpers\FormatPrice::format($restAPayer) }}</td>

                    <td>
                        @if($prestation->payableBy)
                            {{ $prestation->payableBy->nomcomplet_client }}
                        @endif

                        @if($prestation->priseCharge)
                            {{ $prestation->priseCharge->assureur->nom }}
                        @endif
                    </td>
                </tr>
            @endforeach

            <tr class="fw-bold bg-light" style="font-size: 11px; page-break-inside: avoid;">
                <td colspan="6" class="text-end text-uppercase">TOTAUX :</td>
                <td>{{ \App\Helpers\FormatPrice::format($sumAmount) }}</td>
                <td>{{ \App\Helpers\FormatPrice::format($sumAmountPc) }}</td>
                <td>{{ \App\Helpers\FormatPrice::format($sumAmountClient) }}</td>
                <td>{{ \App\Helpers\FormatPrice::format($sumRegulation) }}</td>
                <td>{{ \App\Helpers\FormatPrice::format($sumRemise) }}</td>
                <td>{{ \App\Helpers\FormatPrice::format($sumReste) }}</td>
                <td></td>
            </tr>
            </tbody>
        </table>
    </div>
</div>


</body>
</html>
