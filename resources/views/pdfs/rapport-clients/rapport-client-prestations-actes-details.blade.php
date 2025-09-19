<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport Rendez-vous</title>

    <style>
        {!! $bootstrap !!}
    </style>

    <style>
        body {
            font-size: 13px;
            font-family: "Rubik", sans-serif;
            margin: 10px 15px 20px 15px;
        }


        footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 25px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            padding-top: 5px;
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

    <div class="d-flex justify-content-between align-items-center mb-3" style="gap: 10px;">
        <!-- Logo -->
        <div class="flex-shrink-0">
            <img src="{{ public_path('certificats/logo.png') }}" class="img-fluid" width="230" height="230" alt="Logo">
        </div>

        <!-- Texte -->
        <div class="text-center flex-grow-1">
            <h1 class="text-uppercase fw-bold" style="color: #00b050; font-size: 30px; margin-bottom: 5px;">
                CENTRE MEDICAL GT
            </h1>
            <hr style="width: 80%; border: 1px solid #00b050; margin: 5px auto;">
            <small style="font-size: 10px; color: #00b050; line-height: 1.5;">
                Sis en face du Camp SIC Tsinga – Ouvert de Lundi à Samedi : 7H30 – 18H<br>
                B.P. 6107 Yaoundé - Tél : +237 653 01 01 / 691 53 42 28 / 691 53 03 21<br>
                Boulevard du Sultan Njoya 2.351 / Email : cmgttsinga@yahoo.fr<br>
                Agrément N° 0708/A/MINSANTE/SG/DOSTS du 23 février 2021
            </small>
        </div>
    </div>

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

    <fieldset class="mb-lg-3 mt-3 p-2 rounded-0" style="border: 3px solid #00b050;">
        <h5 class="text-uppercase fs-6 text-center">
            COMPTE RENDU {{ $rapport->prestation->actes->first()?->name ?? 'RAS' }}
        </h5>
    </fieldset>


    <p class="mb-lg-3 mt-3">
        <strong><span class="text-decoration-underline">Technique :</span></strong>
        {{ optional($rapport->techniqueAnalyse)->name ?? 'RAS' }}
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
        <p class="mt-3">
            {{ $rapport->consultant->nomcomplet ?? 'RAS' }}
        </p>
    </div>
    <div class="footer text-center">
        <small  style="font-size: 8px;color: #00b050">
            Médecine générale – Médecine interne – Cardiologie – Dermatologie – Diabétologie – Endocrinologie – Gériatrie – <br>
            Neurologie – Pneumologie – Rhumatologie – Gynécologie – Consultations prénatales – Médecine du Travail – ORL – Urologie <br>
            – Neuropsychologie – Diététique et Nutrition – Imagerie médicale - Kinésithérapie
        </small>
    </div>
</div>

</body>
</html>
