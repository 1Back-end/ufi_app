<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>ETATS REGLEMENTS DES CLIENTS</title>

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
        h1 {
            font-size: 5mm !important;
        }
        header {
            font-family: 'Helvetica', serif;
        }
        img {
            width: auto;
            height: auto;
        }

        .page-number:before {
            content: "Page " counter(page);
        }

        @page {
            margin: 15mm 10mm 20mm 10mm;
            counter-increment: page;
        }
    </style>
</head>
<body>

<div class="col-lg-12 col-sm-12 p-0">


    <header class="d-flex align-items-center size" style="font-family: 'Times New Roman', serif">
        <div class="w-25">
            <img src="{{ public_path('certificats/logo.png') }}" alt=""
                 class="img-fluid w-50">
        </div>

        <div class="text-center" style="line-height: 18px">
            <div class="fs-3 text-uppercase fw-bold">
                CENTRE MEDICAL GT
            </div>

            <div class="">
                Sis en face du Camp SIC Tsinga – Ouvert de Lundi à Samedi : 7H30 – 18H<br>
                B.P. 6107 Yaoundé - Tél : +237 653 01 01 / 691 53 42 28 / 691 53 03 21<br>
                Boulevard du Sultan Njoya 2.351 / Email : cmgttsinga@yahoo.fr<br>
                Agrément N° 0708/A/MINSANTE/SG/DOSTS du 23 février 2021
            </div>


        </div>
    </header>

    <div class="mt-2 w-100" style="border-top: 1px double rgb(0, 0, 0, 0.75); margin-bottom: 2px"></div>
    <div class="mb-2 w-100" style="border-top: 1px double rgb(0, 0, 0, 0.75);"></div>

    <h1 class="fs-3 fw-bold text-center text-uppercase text-decoration-underline">
       ETATS DES REGLEMENTS CLIENTS {{ \Carbon\Carbon::parse($today)->format('d-m-Y') }}
    </h1>

    <p class="fst-italic text-end">Date d'impression: {{ now()->format('d/m/Y H:i') }}</p>

    <h4 class="fs-3 fw-bold text-center text-uppercase text-decoration-underline">
        ETATS DES REGLEMENTS CLIENTS {{ \Carbon\Carbon::parse($today)->format('d-m-Y') }}
    </h4>

    <div class="mt-2 w-100">
        <table class="table table-bordered table-striped text-center">
            <thead>
            <th>ID</th>
            <th>Réference</th>
            <th>Nom complet</th>
            <th>Sexe</th>
            <th>Statut matrimonial</th>
            <th>Téléphone</th>
            <th>Type</th>
            <th>Date naissance</th>
            <th>Date création</th>
            </thead>
            <tbody>
            @foreach ($clients as $client)
                <tr>
                    <td>{{ $client->id }}</td>
                    <td>{{ $client->ref_cli }}</td>
                    <td>{{ $client->nomcomplet_client }}</td>
                    <td>{{ $client->sexe->description_sex ?? '-' }}</td>
                    <td>{{ $client->statusFamiliale->description_statusfam ?? '-' }}</td>
                    <td>
                        {{ $client->tel_cli }} <br>
                        {{ $client->tel2_cli }}
                    </td>
                    <td>{{ ucfirst($client->type_cli) }}</td>
                    <td>{{ \Carbon\Carbon::parse($client->date_naiss_cli)->format('Y-m-d') }}</td>
                    <td>{{ $client->created_at->format('d/m/Y') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>




</div>

</body>
</html>
