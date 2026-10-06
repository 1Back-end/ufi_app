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


    <h3 class="fs-4 fw-bold text-center text-uppercase my-3">
        ÉTAT DES FACTURES RÉGLÉES PAR L'ASSURANCE {{ $firstFacture?->prestation?->priseCharge?->assureur?->nom ?? $assurance->nom ?? '' }}
        <br>
        <small class="fs-5 text-muted">
            Période du {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
        </small>
    </h3>

    <p class="fst-italic text-end">Date d'impression: {{ now()->format('d/m/Y H:i') }}</p>


    <div class="mt-2 w-100">
        <table class="table table-bordered table-striped" style="font-size: 11px;">
            <thead>
            <tr>
                <th>N°</th>
                <th>Date facture</th>
                <th>N° Facture</th>
                <th>Nom patient</th>
                <th>Montant réclamé</th>
                <th>Montant réglé</th>
                <th>Montant exclu</th>
            </tr>
            </thead>

            <tbody>
            @php
                $totalMontantReclame = 0;
                $totalModerateur = 0;
                $totalARegler = 0;
                $firstFacture = $factures->first();
            @endphp

            @foreach($factures as $index => $facture)
                @php
                    $totalMontantReclame += $facture->amount_pc;
                    $totalModerateur += $facture->amount_paid;
                    $totalARegler += $facture->amount_contested;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($facture->date_fact)->format('d/m/Y') }}</td>
                    <td>{{ $facture->code ?? '' }}</td>
                    <td>{{ $facture->prestation->client->nomcomplet_client ?? '' }}</td>
                    <td>{{ \App\Helpers\FormatPrice::format($facture->amount) }}</td>
                    <td class="text-center">{{ \App\Helpers\FormatPrice::format($facture->amount_paid) }}</td>
                    <td class="text-center">{{ \App\Helpers\FormatPrice::format($facture->amount_contested) }}</td>
                    {{-- Supprimé la 8ème cellule en trop pour correspondre aux 7 th --}}
                </tr>
            @endforeach

            <tr class="fw-bold">
                <td colspan="4" class="text-end">Totaux :</td>
                <td>{{ \App\Helpers\FormatPrice::format($totalMontantReclame) }}</td>
                <td class="text-center">{{ \App\Helpers\FormatPrice::format($totalModerateur) }}</td>
                <td class="text-center">{{ \App\Helpers\FormatPrice::format($totalARegler) }}</td>
            </tr>
            </tbody>
        </table>
    </div>
</div>




</body>
</html>
