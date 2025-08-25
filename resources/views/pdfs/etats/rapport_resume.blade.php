<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>ETAT PAR CONSULTANT</title>
    <link rel="stylesheet" href="{{ public_path('certificats/style.css') }}">
    <style>
        {!! $bootstrap !!}
    </style>
    <style>
        body {
            font-size: 13px;
            font-family: "Rubik", sans-serif;
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
            margin: 15mm 20mm 15mm 20mm;
            counter-increment: page;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
        }
        th {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
<div class="container-fluid mt-3 pb-3">
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

    {{-- Titre --}}
    <div class="mb-4 mt-3 text-center">
        <h4 class="text-uppercase" style="color: #00b050; font-size: 22px;">
            ETAT DES CONSULTANTS PAR PRESTATIONS
        </h4>
    </div>

    {{-- Tableau résumé --}}
    <table>
        <thead>
        <tr>
            <th>Consultant</th>
            <th>Nombre RDV</th>
            <th>Actes</th>
            <th>Consultations</th>
            <th>Soins</th>
            <th>Produits</th>
            <th>Examen de laboratoire</th>
            <th>Hospitalisation</th>
        </tr>
        </thead>
        <tbody>
        @foreach($summary as $row)
            <tr>
                <td>{{ $row['consultant'] }}</td>
                <td>{{ $row['nombre_rdv'] }}</td>
                <td>{{ $row['Actes'] }}</td>
                <td>{{ $row['Consultations'] }}</td>
                <td>{{ $row['Soins'] }}</td>
                <td>{{ $row['Produits'] }}</td>
                <td>{{ $row['Examen de laboratoire'] }}</td>
                <td>{{ $row['Hospitalisation'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

</div>
</body>
</html>
