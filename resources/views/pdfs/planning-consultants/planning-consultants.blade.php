<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>PLANNING DES CONSULTANTS</title>

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
            text-align: left;
        }

        .page-break {
            page-break-before: always;
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

<div class="col-md-12 p-0">
    <!-- Logo et infos -->
    <div class="mb-2 d-flex justify-content-between align-items-center">
        <div class="mb-0 mx-2">
            <img src="{{ public_path('certificats/logo.png') }}" class="img-fluid" width="150" height="150">
        </div>
        <div>
            <h5 class="text-uppercase text-center" style="color: #00b050;font-size: 25px;">CENTRE MEDICAL GT</h5>
            <small style="font-size: 9px;color: #00b050">
                Sis en face du Camp SIC Tsinga – Ouvert de Lundi à Samedi : 7H30 – 18H<br>
                B.P. 6107 Yaoundé - Tél : +237 653 01 01 / 691 53 42 28 / 691 53 03 21
            </small>
        </div>
    </div>

    <!-- Titre -->
    <div class="mb-lg-3 mt-5 text-center">
        <h5 class="text-uppercase text-center" style="color: #00b050;font-size: 25px;">Planning des consultants internes</h5>
    </div>

    @php
        use Carbon\Carbon;
        $today = Carbon::now()->format('d-m-Y');
    @endphp

    <p class="text-end small">Date d'impression : {{ $today }}</p>

    <table class="table table-bordered text-center table-striped">
        <thead>
        <tr>
            <th>#</th>
            <th>Nom complet</th>
            <th>Téléphone</th>
            <th>Email</th>
            <th>Type</th>
            <th>Disponibilités</th>
        </tr>
        </thead>
        <tbody>
        @foreach($consultants as $index => $consultant)
            @if($consultant->disponibilites->isNotEmpty())
            <tr>
                <td>{{ $consultant->id }}</td>
                <td class="text-uppercase">{{ $consultant->nomcomplet }}</td>
                <td>{{ $consultant->tel }}</td>
                <td>{{ $consultant->email }}</td>
                <td>{{ $consultant->type }}</td>
                <td>
                    @php
                        $disposGrouped = [];
                        foreach($consultant->disponibilites as $d) {
                            $jourNom = match($d->jour) {
                                1 => 'Lundi',
                                2 => 'Mardi',
                                3 => 'Mercredi',
                                4 => 'Jeudi',
                                5 => 'Vendredi',
                                6 => 'Samedi',
                                7 => 'Dimanche',
                                default => 'Inconnu',
                            };
                            $disposGrouped[$jourNom][] = $d->heure_debut->format('H:i') . ' - ' . $d->heure_fin->format('H:i');
                        }
                    @endphp

                    @foreach($disposGrouped as $jour => $creneaux)
                        <div class="mb-2 text-center">
                            <strong>{{ $jour }}</strong><br>
                            @foreach($creneaux as $creneau)
                                <span class="d-block">{{ $creneau }}</span>
                            @endforeach
                        </div>
                    @endforeach
                </td>
            </tr>
            @endif
        @endforeach
        </tbody>

    </table>



</div>

<div class="footer text-center">
    <small  style="font-size: 8px;color: #00b050">
        Médecine générale – Médecine interne – Cardiologie – Dermatologie – Diabétologie – Endocrinologie – Gériatrie – <br>
        Neurologie – Pneumologie – Rhumatologie – Gynécologie – Consultations prénatales – Médecine du Travail – ORL – Urologie <br>
        – Neuropsychologie – Diététique et Nutrition – Imagerie médicale - Kinésithérapie
    </small>
</div>

</body>
</html>
