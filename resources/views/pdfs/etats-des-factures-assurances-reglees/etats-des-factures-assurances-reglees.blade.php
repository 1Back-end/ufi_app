<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>ETATS DES REGLEMENTS DE FACTURES DES ASSURANCES</title>

    <style>
        {!! $bootstrap !!}
    </style>

    <style>
        @page {
            margin: 5mm 10mm;
        }

        header {
            margin: 0;
            padding: 0;
            font-size: 3mm !important;
            font-family: "Times New Roman", serif;
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
        header {
            margin: 0 !important;
            padding: 0 !important;
            line-height: 12px;
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
        ÉTATS DES FACTURES RÉGLÉES PAR {{ $assureur->nom }}
    </h1>

    <p class="fst-italic text-end">Date d'impression: {{ now()->format('d/m/Y H:i') }}</p>

    <h4 class="fw-bold text-center fs-5 text-uppercase">
        <span class="fs-6">
        Période : du {{ \Carbon\Carbon::parse($start)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($end)->format('d/m/Y') }}
    </span>
    </h4>


    <div class="mt-2 w-100">
        <table class="table table-bordered table-striped border-black" style="font-size: 10px;">
            <thead>
            <tr>
                <th>#</th>
                <th>Date facture</th>
                <th>Code facture</th>
                <th>Nom client</th>
                <th>Montant PC</th>
                <th>Montant client</th>
                <th>Montant proraté</th>
                <th>Montant contesté</th>
                <th>Montant payé</th>
                <th>Retenu IR</th>
                <th>Montant perçu</th>
                <th>Autres</th>
            </tr>
            </thead>

            <tbody>
            @php
                $i = 1;
                $shownFactures = [];
            @endphp

            @foreach($result ?? [] as $regulation)

                @foreach($regulation->assurance?->priseEnCharges ?? [] as $pec)

                    @foreach($pec->prestations ?? [] as $prestation)

                        @foreach($prestation->factures ?? [] as $facture)

                            @if(!$facture || isset($shownFactures[$facture->id]))
                                @continue
                            @endif

                            @php
                                $shownFactures[$facture->id] = true;
                            @endphp

                            <tr>
                                <td>{{ $i++ }}</td>

                                <td>
                                    {{ $facture->date_fact
                                        ? \Carbon\Carbon::parse($facture->date_fact)->format('d/m/Y')
                                        : '-' }}
                                </td>

                                <td>{{ $facture->code ?? '-' }}</td>

                                <td>
                                    {{ optional($prestation->client)->nomcomplet_client ?? '-' }}
                                </td>

                                <td>
                                    {{ \App\Helpers\FormatPrice::format($facture->amount_pc) }}
                                </td>

                                <td>
                                    {{ \App\Helpers\FormatPrice::format($facture->amount_client) }}
                                </td>
                                <td>
                                    {{ \App\Helpers\FormatPrice::format($facture->amount_prorate) }}
                                </td>
                                <td>
                                    {{ \App\Helpers\FormatPrice::format($facture->amount_contested) }}
                                </td>
                                <td>
                                    {{ \App\Helpers\FormatPrice::format($facture->amount_paid) }}
                                </td>
                                <td>
                                    {{ \App\Helpers\FormatPrice::format($facture->amount_ir) }}
                                </td>
                                <td>
                                    {{ \App\Helpers\FormatPrice::format($facture->amount_received) }}
                                </td>
                                <td>
                                    {{ \App\Helpers\FormatPrice::format($facture->others_amount_excluded) }}
                                </td>
                            </tr>

                        @endforeach

                    @endforeach

                @endforeach

            @endforeach
            </tbody>
        </table>
        <div class="mt-3">
            <h6 class="fw-bold text-uppercase  mb-2">
                Détails des règlements
            </h6>

            <table  class="table table-bordered table-striped border-black" style="font-size: 12px;">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Numéro pièce</th>
                    <th>Mode de règlement</th>
                    <th>Date de la pièce</th>
                    <th>Date de reception</th>
                    <th>Montant attendu</th>
                    <th>Montant payé</th>
                </tr>
                </thead>

                <tbody>
                @php $i = 1; @endphp

                @foreach($result ?? [] as $regulation)
                    <tr>
                        <td>{{ $i++ }}</td>

                        <td>
                            {{ $regulation->number_piece ?? 'N/A' }}
                        </td>

                        <td>
                            {{ $regulation->regulationMethod->name ?? '-' }}
                        </td>

                        <td>
                            {{ \Carbon\Carbon::parse($regulation->date_piece)->format('d/m/Y') ?? '-' }}
                        </td>
                        <td>
                            {{ \Carbon\Carbon::parse($regulation->date_reception)->format('d/m/Y') ?? '-' }}
                        </td>
                        <td class="fw-bold">
                            {{ \App\Helpers\FormatPrice::format($regulation->amount_waiting ?? 0) }}
                        </td>

                        <td class="fw-bold">
                            {{ \App\Helpers\FormatPrice::format($regulation->amount ?? 0) }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

</div>

</body>
</html>
