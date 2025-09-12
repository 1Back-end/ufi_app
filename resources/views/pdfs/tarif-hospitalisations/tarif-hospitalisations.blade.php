<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tarifaire des hospitalisations</title>

    <style>
        {!! $bootstrap !!}
    </style>

    <style>
        body {
            font-size: 13px;
            font-family: "Rubik", sans-serif;
            margin: 10px 15px 20px 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 5px;
            text-align: center;
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
        @page {
            margin: 15mm 10mm 20mm 10mm;
        }
    </style>
</head>
<body>

<div class="col-md-12 p-0">
    <!-- Logo et infos -->
    <div class="mb-2 d-flex justify-content-between align-items-center">
        <div class="mb-0 mx-2">
            <img src="{{ public_path('certificats/logo.png') }}" class="img-fluid" width="150" height="150">
        </div>
        <div>
            <h5 class="text-uppercase text-center" style="color: #00b050; font-size: 25px;">
                CENTRE MEDICAL GT
            </h5>
            <small style="font-size: 9px; color: #00b050;">
                Sis en face du Camp SIC Tsinga – Ouvert de Lundi à Samedi : 7H30 – 18H<br>
                B.P. 6107 Yaoundé - Tél : +237 653 01 01 / 691 53 42 28 / 691 53 03 21
            </small>
        </div>
    </div>

    <!-- Titre -->
    <div class="mb-lg-3 mt-5 text-center">
        <h5 class="text-uppercase" style="color: #00b050; font-size: 25px;">
            Tarifaire des hospitalisations
        </h5>
    </div>

    @php
        use Carbon\Carbon;
        $today = Carbon::now()->format('d-m-Y');
    @endphp
    <p class="text-end small">Date d'impression : {{ $today }}</p>

    <!-- Tableau des hospitalisations -->
    <table class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>N°</th>
            <th>Hospitalisation</th>
            <th>PU Assuré</th>
            <th>PU Client</th>
        </tr>
        </thead>
        <tbody>
        @forelse($hospitalisations as $index => $hosp)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $hosp->name }}</td>
                <td>{{ number_format($hosp->pu, 0, ',', ' ') }} FCFA</td>
                <td>{{ number_format($hosp->pu_default, 0, ',', ' ') }} FCFA</td>
            </tr>
        @empty
            <tr>
                <td colspan="12">Aucune hospitalisation disponible.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<footer>
    <small style="font-size: 8px; color: #00b050;">
        Médecine générale – Médecine interne – Cardiologie – Dermatologie – Diabétologie – Endocrinologie – Gériatrie –<br>
        Neurologie – Pneumologie – Rhumatologie – Gynécologie – Consultations prénatales – Médecine du Travail – ORL – Urologie –<br>
        Neuropsychologie – Diététique et Nutrition – Imagerie médicale - Kinésithérapie
    </small>
</footer>

</body>
</html>
