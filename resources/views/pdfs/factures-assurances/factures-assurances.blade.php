<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>FACTURES ASSURANCES</title>

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


    @php
        $firstFacture = $factures->first();
    @endphp

    <div class="row">
        <div class="col-4"></div>
        <div class="text-dark mt-2 col">
            <p class="text-center">{{ $centre->town }}, le {{ now()->translatedFormat('j F Y') }}</p>

            <div class="mt-2 text-center">
                <p class="my-0">A</p>
                <p class="my-0">L'attention de Monsieur le Directeur Général</p>
                <p>{{ $firstFacture->prestation->priseCharge->assureur->nom ?? '' }}</p>
                <p>N° Contribuable{{ $firstFacture->prestation->priseCharge->assureur->num_com ?? '' }}</p>
                <p>BP {{ $firstFacture->prestation->priseCharge->assureur->bp ?? '' }}</p>
            </div>
        </div>
    </div>

    <p class="text-center w-100 text-uppercase text-decoration-underline">
        FACTURE N°{{ $firstFacture->prestation->priseCharge->assureur->id ?? '' }}/
        {{ $centre->name }}/
        {{ $firstFacture->prestation->priseCharge->assureur->number_facture
        ?? $firstFacture->prestation->priseCharge->assureur->nom_abrege ?? '' }}/
        {{ now()->format('d-Y') }}
    </p>

    <div class="">
        <span class="text-decoration-underline">Objet:</span>
        <span>Analyse médicale de vos assurés</span>

        <p>Période: du {{ $startDate }} au {{ $endDate }}</p>
    </div>



    <div class="mt-2 w-100">
        <table class="table table-bordered table-striped" style="font-size: 11px;">
            <thead>
            <tr>
                <th>N°</th>
                <th>Date facture</th>
                <th>N° Facture</th>
                <th>Nom patient</th>
                <th>Montant Reclamé</th>
                <th>Modérateur</th>
                <th>Montant à réglé</th>
            </tr>
            </thead>

            <tbody>
            @php
                $totalHR = 0;
                $totalMontantReclame = 0;
                $totalModerateur = 0;
                $totalARegler = 0;
            @endphp

            @foreach($factures as $index => $facture)
                @php
                    // Totaux des colonnes
                    $totalMontantReclame += $facture->amount_pc;
                    $totalModerateur += $facture->amount_client;
                    $totalARegler += ($facture->amount_pc - $facture->amount_client);
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($facture->date_fact)->format('d/m/Y') }}</td>
                    <td>{{ $facture->code ?? '' }}</td>
                    <td>{{ $facture->prestation->client->nomcomplet_client ?? '' }}</td>
                    <td>{{ \App\Helpers\FormatPrice::format($facture->amount_pc) }}</td>
                    <td class="text-center">{{ \App\Helpers\FormatPrice::format($facture->amount_client) }}</td>
                    <td class="text-center">{{ \App\Helpers\FormatPrice::format($facture->amount_pc - $facture->amount_client) }}</td>
                </tr>
            @endforeach

            {{-- Ligne Totaux avant HR --}}
            <tr class="fw-bold">
                <td colspan="4" class="text-end">Montant Hors Taxe:</td>
                <td>{{ \App\Helpers\FormatPrice::format($totalMontantReclame) }}</td>
                <td class="text-center">{{ \App\Helpers\FormatPrice::format($totalModerateur) }}</td>
                <td class="text-center">{{ \App\Helpers\FormatPrice::format($totalARegler) }}</td>
            </tr>


            @php
                $taux = $firstFacture->prestation->priseCharge->assureur->taux_retenu ?? 0;
                $totalHR = ($totalARegler * $taux / 100);
                $netAPayer = $totalARegler - $totalHR;
            @endphp

            {{-- Ligne HR alignée sous Montant à régler --}}
            @if($totalHR > 0)
                <tr class="fw-bold">
                    <td colspan="4" class="text-end">HR ({{ $taux }}%) :</td>
                    <td colspan="10" class="text-end">{{ \App\Helpers\FormatPrice::format($totalHR) }}</td>
                </tr>
            @endif


            <tr class="fw-bold">
                <td colspan="4" class="text-end">Montant Total Net à payer :</td>
                <td colspan="3" class="text-end">{{ \App\Helpers\FormatPrice::format($netAPayer) }}</td>
            </tr>

            </tbody>
        </table>
    </div>




</body>
</html>
