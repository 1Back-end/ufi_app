@php use Carbon\Carbon; @endphp
    <!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>ETATS DES PRESTATIONS DES CONSULTANTS NON REGLES</title>

    <style>
        {!! $bootstrap !!}
    </style>

    <style>
        @page {
            margin: 5mm 5mm 8mm 5mm; /* 🔥 réduit les marges PDF */
        }

        html, body {
            margin: 0 !important;
            padding: 0 !important;
            font-family: "Times New Roman", serif;
            font-size: 3mm !important;
            width: 100%;
        }

        .print-wrapper {
            margin: 0 !important;
            padding: 0 !important;
            width: 100%;
        }

        h1, h2, h3, h4, h5, h6 {
            margin: 2px 0 !important;
            padding: 0 !important;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 !important;
            padding: 0 !important;
            font-size: 3mm !important;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
        }

        td, th {
            padding: 2px 3px !important; /* 🔥 réduit espace cellules */
        }

        .print-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 8mm;
            text-align: center;
            font-size: 3mm;
        }

        img {
            max-width: 100%;
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
                - {{ $centre->address }}
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


    <h4 class="fs-3 fw-bold text-center text-uppercase">
        ÉTAT DES PRESTATIONS DU CONSULTANT {{ $consultant->nomcomplet ?? '-' }}
    </h4>

    <p class="fst-italic text-end">Date d'impression: {{ now()->format('d/m/Y H:i') }}</p>

    @if(isset($start) && isset($end))
        <h2 class="fw-bold text-center fs-5 text-uppercase">
            <strong>Période :</strong>
            du {{ \Carbon\Carbon::parse($start)->format('d/m/Y') }}
            au {{ \Carbon\Carbon::parse($end)->format('d/m/Y') }}
        </h2>
    @endif

    <div class="mt-2 w-100">
        <table class="table table-bordered table-striped table-sm" style="font-size: 10px;">
            <thead>
            <tr class="bg-dark text-white">
                <th>#</th>
                <th>Date</th>
                <th>Patient</th>
                <th>Montant prestation</th>
                <th>Montant consultant</th>
                <th>Elements</th>
                <th>Prise en charge</th>
                <th>Client associé</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($result as $index => $prestation)
                @php
                    $facture = $prestation->factures->first();
                    $factureReglee = $prestation->factures->filter(fn($f) => $f->regulations && $f->regulations->where('state', 1)->count() > 0)->first();
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        {{ \Carbon\Carbon::parse($prestation->created_at)->format('d/m/Y H:i') }}
                    </td>
                    <td>{{ optional($prestation->client)->nomcomplet_client }}</td>
                    <td>{{ \App\Helpers\FormatPrice::format(optional($facture)->amount) }}</td>
                    <td>
                        {{ \App\Helpers\FormatPrice::format($prestation->consultant_amount) }}
                    </td>
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
                        @if($prestation->priseCharge)
                            {{ $prestation->priseCharge->assureur?->nom ?? '' }}
                        @else
                            NON
                        @endif
                    </td>

                    <td>
                        @if($prestation->payableBy)
                            {{ $prestation->payableBy->nomcomplet_client }}
                        @else
                            NON
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
