<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>RAPPORT RENDEZ-VOUS</title>

    <link rel="stylesheet" href="{{ public_path('certificats/style.css') }}">

    <style>
        {!! $bootstrap !!}
    </style>

    <style>
        body {
            font-size: 14px;
            font-family: "Rubik", sans-serif;
            margin: 15px 20px 25px 20px;
            color: #222;
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
            font-size: 12px;
        }

        .page-number:before {
            content: "Page " counter(page);
        }

        @page {
            margin: 15mm 10mm 20mm 10mm;
            counter-increment: page;
        }

        h4 {
            color: #00b050;
            font-size: 24px;
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th, td {
            padding: 6px 8px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #00b050;
            color: white;
            text-transform: uppercase;
            font-size: 12px;
        }

        .header-info {
            font-size: 11px;
            color: #00b050;
            text-align: right;
        }

        .filter-info {
            font-size: 12px;
            color: #555;
            text-transform: uppercase;
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
            <h5 class="text-uppercase text-center" style="color: #00b050;font-size: 25px;">CENTRE MEDICAL GT</h5>
            <small style="font-size: 9px;color: #00b050">
                Sis en face du Camp SIC Tsinga – Ouvert de Lundi à Samedi : 7H30 – 18H<br>
                B.P. 6107 Yaoundé - Tél : +237 653 01 01 / 691 53 42 28 / 691 53 03 21
            </small>
        </div>
    </div>

    {{-- Titre --}}
    <div class="text-center mt-4 mb-4">
        <h4 class="text-uppercase">ÉTAT CONSULTATION</h4>
    </div>

    {{-- Filtres --}}
    <div class="d-flex justify-content-center flex-wrap gap-3 mb-4 filter-info">
        @if(!empty($data['periode']))
            <div><strong>Période :</strong> {{ $data['periode']['du'] }} au {{ $data['periode']['au'] }}</div>
        @endif

        @if(!empty($data['filtre']['consultant_id']))
            <div>
                <strong>Consultant :</strong>
                {{ $data['rendezVous']->firstWhere('consultant.id', $data['filtre']['consultant_id'])->consultant->nomcomplet ?? 'N/A' }}
            </div>
        @endif

        @if(!empty($data['filtre']['client_id']))
            <div>
                <strong>Client :</strong>
                {{ $data['rendezVous']->firstWhere('client.id', $data['filtre']['client_id'])->client->nomcomplet_client ?? 'N/A' }}
            </div>
        @endif

        @if(!empty($data['filtre']['type']))
            <div>
                <strong>Type de consultation :</strong>
                {{ $data['rendezVous']->first()?->prestation?->type_label ?? $data['filtre']['type'] }}
            </div>
        @endif
    </div>

    {{-- Tableau --}}
    <div class="mt-3">
        <table class="table table-striped table-bordered text-center">
            <thead>
            <tr>
                <th>Code</th>
                <th>Date</th>
                <th>Heure</th>
                <th>Consultant</th>
                <th>Client</th>
                <th>Sexe</th>
                <th>Téléphone</th>
                <th>Type consultation</th>
                <th>Assuré ?</th>
                <th>Nom assurance</th>
            </tr>
            </thead>
            <tbody>
            @foreach($data['rendezVous'] as $rdv)
                <tr>
                    <td>{{ $rdv->code }}</td>
                    <td>{{ \Carbon\Carbon::parse($rdv->dateheure_rdv)->format('d/m/Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($rdv->dateheure_rdv)->format('H:i') }}</td>
                    <td>{{ $rdv->consultant->nomcomplet }}</td>
                    <td>{{ $rdv->client->nomcomplet_client }}</td>
                    <td>{{ $rdv->client->sexe->description_sex }}</td>
                    <td>{{ $rdv->client->tel_cli }}</td>
                    <td>{{ $rdv->prestation->type_label }}</td>
                    <td>{{ $rdv->client->assure_pa_cli ? 'Oui' : 'Non' }}</td>
                    <td>
                        {{ $rdv->client->assure_pa_cli && !empty($rdv->client->nom_assure_principale_cli)
                            ? $rdv->client->nom_assure_principale_cli
                            : 'RAS' }}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
