<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>ETATS PRESTATIONS DES ASSURANCES</title>

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
                <th style="font-style: italic;font-size: 11px">Eléments</th>
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
                $totalAmount = 0;
                $totalAmountPc = 0;
                $totalAmountClient = 0;
                $totalRegulation = 0;
                $totalRemise = 0;
                $totalRestAPayer = 0;
            @endphp
            @foreach ($prestations as $index => $prestation)
                @php
                    // Récupérer la facture principale
                    $facture = $prestation->factures->first();

                    // Récupérer la facture qui contient un règlement
                    $factureReglee = $prestation->factures
                        ->filter(fn($f) => $f->regulations && $f->regulations->where('state', 1)->count() > 0)
                        ->first();

                    // Total des règlements validés
                    $regulationAmount = $factureReglee
                        ? $factureReglee->regulations->where('state', 1)->sum('amount')
                        : 0;

                    // Calcul du reste à payer
                    $restAPayer = ($factureReglee ? $factureReglee->amount_client : 0) - $regulationAmount;
                    $totalAmount += optional($facture)->amount ?? 0;
                    $totalAmountPc += optional($facture)->amount_pc ?? 0;
                    $totalAmountClient += optional($facture)->amount_client ?? 0;
                    $totalRegulation += $regulationAmount;
                    $totalRemise += optional($facture)->amount_remise ?? 0;
                    $totalRestAPayer += $restAPayer;
                @endphp

                <tr>
                    <td>{{ $facture ? $facture->code : "Facture non créée" }}</td>
                    <td>{{ $prestation->created_at?->format('d/m/Y') }}</td>
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
            </tbody>
        </table>
        <div class="card mt-3" style="page-break-inside: avoid;">

            <div class="card-header fw-bold text-center text-uppercase" style="background: #f1f1f1; font-size: 14px;">
                Totaux généraux
            </div>

            <div class="card-body p-2">

                <div class="row text-center fw-bold" style="font-size: 14px; line-height: 1.6;">

                    <div class="col">
                        Montant Total<br>
                        {{ \App\Helpers\FormatPrice::format($totalAmount) }}
                    </div>

                    <div class="col">
                        Montant PC<br>
                        {{ \App\Helpers\FormatPrice::format($totalAmountPc) }}
                    </div>

                    <div class="col">
                        Part patient<br>
                        {{ \App\Helpers\FormatPrice::format($totalAmountClient) }}
                    </div>

                    <div class="col">
                        Montant
                        payé
                        patient<br>
                        {{ \App\Helpers\FormatPrice::format($totalRegulation) }}
                    </div>

                    <div class="col">
                        Remise<br>
                        {{ \App\Helpers\FormatPrice::format($totalRemise) }}
                    </div>

                    <div class="col">
                        Reste à payer client<br>
                        {{ \App\Helpers\FormatPrice::format($totalRestAPayer) }}
                    </div>

                </div>

            </div>

        </div>
    </div>
</div>

</body>
</html>
