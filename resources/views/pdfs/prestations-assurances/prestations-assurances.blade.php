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
        ETATS DES JOURNALIERS CLIENTS
    </h1>

    <p class="fst-italic text-end">Date d'impression: {{ now()->format('d/m/Y H:i') }}</p>

    <h2 class="fw-bold text-center fs-5 text-uppercase">
        {{ $centre->name }} - {{ $titre }}
    </h2>


    <div class="mt-2 w-100">
        <table class="table table-bordered table-striped text-center border-black" style="font-size: 2.5mm;">
            <thead>
            <tr>
                <th>N° Facture</th>
                <th>Création DT</th>
                <th>Nom patient</th>
                <th>Prescripteur</th>
                <th>Eléments</th>
                <th>Pris en charge</th>
                <th>Societé / Partenaire</th>
                <th>Montant Total</th>
                <th>Part patient</th>
                <th>Montant payé patient</th>
                <th>Montant Remise</th>
                <th>Reste à payer client</th>
                <th>Assurance</th>
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
                    <td>{{ optional($prestation->client)->nomcomplet_client }}</td>
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

                    <td style="width: 10% !important;">
                        {{ $prestation->priseCharge ? 'OUI' : 'NON' }}
                    </td>

                    <td>
                        {{ optional($prestation->client)->societe?->nom_soc_cli }}
                    </td>

                    <td>{{ \App\Helpers\FormatPrice::format(optional($facture)->amount) }}</td>
                    <td>{{ \App\Helpers\FormatPrice::format(optional($facture)->amount_client) }}</td>
                    <td>{{ \App\Helpers\FormatPrice::format(optional($facture)->amount_client) }}</td>
                    <td>{{ \App\Helpers\FormatPrice::format(optional($facture)->amount_remise) }}</td>

                    @php
                        $totalPaid = $facture ? $facture->regulations->where('particular', false)->sum('amount') : 0;
                        $restAPayer = optional($facture)->amount_client - $totalPaid;
                    @endphp

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
            </tbody>
        </table>
    </div>
</div>


</body>
</html>
