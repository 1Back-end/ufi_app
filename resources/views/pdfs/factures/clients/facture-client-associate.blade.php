<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

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
            border-top: 1mm solid rgb(15,187,105);
            opacity: .5;
            font-size: 2.5mm;
        }
    </style>

    <title>{{ $code }}</title>
</head>
<body>
{{-- Header --}}
<header class="row">
    <div class="col-4">
        @if($logo)
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path($logo))) }}" alt="">
        @endif
    </div>

    <div class="text-center col text-success" >
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

<div class="row">
    <div class="col-4"></div>
    <div class="text-dark mt-2 text-center col">
        <p>{{ $centre->town }}, le {{ now()->translatedFormat('j F Y') }}</p>

        <div class="mt-2">
            <p class="my-0">A</p>

            <p class="my-0">L'attention de notre client associé </p>
            <p>{{ $client->nomcomplet_client }}</p>
        </div>
    </div>
</div>

<p class="text-center w-100 text-decoration-underline">
    FACTURE N° {{ $code }}
</p>

<div class="">
    <span class="text-decoration-underline">Objet:</span>
    <span></span>

    <p>Période: du {{ $start_date->translatedFormat('j F Y') }} au {{ $end_date->translatedFormat('j F Y') }}</p>
</div>

@php
    $i = 1;
@endphp

<div class="d-flex justify-content-center mt-2">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>N°</th>
                <th>N° Facture</th>
                <th>Date</th>
                <th>Nom du patient</th>
                <th class="text-center">Montant Réclamé</th>
                <th class="text-center">Modérateur</th>
            </tr>
        </thead>
        <tbody>
        @foreach($prestations as $prestation)
            @foreach($prestation->factures as $facture)
                <tr>
                    <td>
                        {{ $i++ }}
                    </td>
                    <td>{{ $facture->code }}</td>
                    <td>{{ $facture->date_fact->format('d/m/Y') }}</td>
                    <td>{{ $prestation->client->nomcomplet_client }}</td>
                    <td class="text-center">
                        {{ \App\Helpers\FormatPrice::format($facture->amount) }}
                    </td>
                    <td class="text-center">
                        {{ \App\Helpers\FormatPrice::format($facture->amount_client) }}
                    </td>
                </tr>
            @endforeach
        @endforeach
        </tbody>
    </table>
</div>
</body>
</html>
