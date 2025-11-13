<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport Rendez-vous</title>

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

    <div class="mt-3 mb-3">
        <p class="mb-2"><strong>Noms(s) et Prénom(s) :</strong> {{ $rapport->rendezVous->client->nomcomplet_client ?? 'RAS' }}</p>
        <div class="mb-2"><strong>Âge :</strong> {{ $rapport->rendezVous->client->age ?? 'RAS' }} ans</div>
        <div class="mb-2"><strong>Sexe :</strong> {{ $rapport->rendezVous->client->sexe->description_sex ?? 'RAS' }}</div>
        <div class="mb-2"><strong>Téléphone :</strong> {{ $rapport->rendezVous->client->tel_cli ?? 'RAS' }}</div>
        <div class="mb-2"><strong>Renseignement(s) clinique(s) :</strong> {{ $rapport->rendezVous->client->renseign_clini_cli ?? 'RAS' }}</div>

        <p class="mb-2">
            <strong>Médecin Traitant :</strong>
            {{ $rapport->rendezVous->consultant->nomcomplet ?? 'RAS' }}
        </p>
        <p class="mb-2">
            <strong>Examen demandé :</strong>
            {{ $rapport->prestation->type_label ?? 'RAS' }}
        </p>
    </div>

    <h1 class="fs-3 fw-bold text-center text-uppercase text-decoration-underline">
        COMPTE RENDU {{ $rapport->prestation->actes->first()?->name ?? 'RAS' }}
    </h1>

    <p class="fst-italic text-end">Date d'impression: {{ now()->format('d/m/Y H:i') }}</p>

    <p class="mb-lg-3 mt-3" style="font-weight: bold;">
        <strong><span class="text-decoration-underline">Technique :</span></strong>
        {{ $rapport->technique_analyse ?? 'RAS' }}
    </p>

    <p class="mb-lg-3 mt-3 text-decoration-underline"><strong>Résultats :</strong></p>
    <ul class="mb-1">
        @foreach(explode("\n", $rapport->resume ?? '') as $line)
            @php
                $cleanLine = trim($line);
                $cleanLine = ltrim($cleanLine, "- ");
            @endphp
            @if($cleanLine !== '')
                <li>{{ $cleanLine }}</li>
            @endif
        @endforeach
    </ul>

    <p class="mb-lg-3 mt-3">
        <strong><span class="text-decoration-underline">Conclusion :</span></strong><br>
    <ul class="mb-1">
        <li>{{ $rapport->conclusion ?? 'RAS' }}</li>
    </ul>
    </p>

    <div class="text-end mt-4">
        <p class="mt-3">
            Ydé, le {{ \Carbon\Carbon::parse($rapport->created_at)->format('d/m/Y H:i:s') }}
        </p>
        <p class="mt-3" style="font-weight: bold;">
            {{ $rapport->medecin_signataire ?? 'RAS' }}
        </p>
    </div>


</div>

</body>
</html>
