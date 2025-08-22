<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>CERTIFICAT MEDICAL D’APTITUDE AU TRAVAIL</title>

    <style>
        {!! $bootstrap !!}
    </style>


    <style>
        body {
            font-size: 13px;
            font-family: "Rubik", sans-serif;
            margin: 0 30px 50px 30px;
        }

        .page-break {
            page-break-before: always;
        }
        footer {
            position: fixed;
            bottom: 10px;
            left: 0;
            right: 0;
            height: 40px;
            border-top: 1px solid #ddd;
            text-align: center;
            padding-top: 5px;
        }
        .page-number:before {
            content: "Page " counter(page);
        }
        @page {
            margin: 30mm 15mm 30mm 15mm;
            counter-increment: page;
        }
    </style>
</head>
<body><div class="container mt-3 pb-3">

    <!-- Entête avec logo et coordonnées -->
    <div class="mb-2 d-flex justify-content-between align-items-center">
        <div class="mb-0 mx-2">
            <img src="{{ public_path('certificats/logo.png') }}" class="img-fluid" width="150" height="150">
        </div>
        <div>
            <small style="font-size: 9px;color: #00b050">
                Sis en face du Camp SIC Tsinga – Ouvert de Lundi à Samedi : 7H30 – 18H<br>
                B.P. 6107 Yaoundé - Tél : +237 653 01 01 / 691 53 42 28 / 691 53 03 21
            </small>
        </div>
    </div>

    <!-- Titre -->
    <div class="mb-lg-3 mt-5 text-center">
        <h5 class="text-uppercase" style="color: #00b050;font-size: 18px;">
            CERTIFICAT MÉDICAL D’APTITUDE AU TRAVAIL
        </h5>
    </div>

    <!-- Informations du client -->
    <div class="mt-3">
        <p class="mb-xxl-3"><strong>Nom du personnel :</strong> {{ optional($client->prefix)->prefixe ?? 'RAS' }} {{ $client->nomcomplet_client ?? 'RAS' }}</p>
        <p class="mb-xxl-3"><strong>Âge :</strong> {{ $client->age ?? 'RAS' }} ans</p>
        <p class="mb-xxl-3"><strong>Unité et localité :</strong> {{ optional($client->societe)->nom_soc_cli ?? 'RAS' }}</p>
        <p class="mb-xxl-3"><strong>Contact :</strong> {{ $client->tel_cli ?? 'RAS' }}</p>
    </div>

    <!-- Type de visite -->
    <div class="mt-3">
        <p class="mb-xxl-3"><strong>Catégorie visite :</strong> {{ $categorie_visite ?? 'Visite d’embauche' }}</p>
    </div>

    <!-- Conclusion -->
    <div class="mt-3">
        <p class="mb-xxl-3"><strong>Conclusion :</strong> {{ $rapport->conclusion }}</p>
        <p class="mb-xxl-3"><strong>Recommandation(s) :</strong> {{ $rapport->recommandations }}</p>
        <p class="mb-xxl-3"><strong>Date de délivrance du certificat :</strong> {{ $certificat->created_at->format('d/m/Y') }}</p>

    </div>


    <!-- Signature -->
    <div class="text-end mt-3">

        <p class="mb-3" style="color: #00b050"><strong>LE MÉDECIN DU TRAVAIL</strong></p>
    </div>

    <!-- Footer -->
    <p class="text-lg-start mt-4" style="font-size: 8px; color: #00b050">
        Votre santé est notre priorité
    </p>


</div>
