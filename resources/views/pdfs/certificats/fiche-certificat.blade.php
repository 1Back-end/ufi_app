<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>CERTIFICAT MEDICAL</title>

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
<body>

<div class="container mt-3 pb-3">
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

    <div class="mb-lg-3 mt-5 text-center">
        <h5 class="text-uppercase text-center" style="color: #00b050;font-size: 25px;">CERTIFICAT MEDICAL</h5>
    </div>

    <p class="mt-4" style="line-height: 1.8;font-size: 12px;text-align: justify" >
        Je soussigné, <br> <strong>Docteur {{ $consultant->nomcomplet }}</strong>, certifie avoir examiné ce jour le/la
        patient(e) <strong>{{ $client->nomcomplet_client }}</strong> et que son état nécessite un repos de
        <strong>{{ $certificat->nbre_jour_repos }}</strong> jour(s) à partir du <strong>{{ $certificat->created_at }}</strong>,
        sauf complication(s).
    </p>

    <div class="text-end mt-3">
        <p class="mb-3">
            Fait à Yaoundé, le {{ $certificat->created_at }}
        </p>
        <p class="mb-3" style="color: #00b050">
            Cachet et signature du Médecin
        </p>
    </div>

    <p class="text-lg-start mt-4" style="font-size: 8px; color: #00b050">
        Votre santé est notre priorité
    </p>
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
