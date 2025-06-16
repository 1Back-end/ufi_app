<!doctype html>
<html lang="fr">

<head>
    <meta charset="UTF-8">

    <style>
        {!! $bootstrap !!}
    </style>

    <style>
        body,
        html {
            height: 100%;
            margin: 0;
            padding: 0;
            font-size: 3mm !important;
            font-family: "Times New Roman", serif;
        }

        header {
            font-family: 'Helvetica', serif;
        }

        h1 {
            font-size: 6mm !important;
        }

        table {
            font-size: 3mm !important;
        }

        img {
            width: auto;
            height: auto;
        }

        .print-wrapper {
            position: relative;
            min-height: 100%;
            padding-bottom: 10mm;
            box-sizing: border-box;
        }

        .print-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 10mm;
            text-align: center;
            background-color: white;
            border-top: 1mm solid rgb(15, 187, 105);
            opacity: .5;
            font-size: 2.5mm;
        }
    </style>
</head>

<body>
    {{-- Header --}}
    <header class="row pb-2 border-bottom border-1 border-black">
        <div class="col-4">
            @if($logo)
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path($logo))) }}" alt="">
            @endif
        </div>

        <div class="text-center col text-success">
            <div class="fs-1 text-uppercase">
                {{ $centre->name }}
            </div>

            <hr class="my-1 border border-success border-1 opacity-75 col-12">

            <div class="">
                - {{ $centre->address }} - {{ $centre->town }}
            </div>

            <div class="">
                BP: {{ $centre->postal_code }} {{ $centre->town }} -
                Tél. {{ $centre->tel }} {{ $centre->tel2 ? '/' . $centre->tel2 : '' }}
                / Fax: {{ $centre->fax }}
            </div>

            <div class="">
                Email: {{ $centre->email }}
            </div>

            <div class="">
                Autorisation n° {{ $centre->autorisation }}
                NUI: {{ $centre->contribuable }}
            </div>
        </div>
    </header>

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

</body>

</html>
