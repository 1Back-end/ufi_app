<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>RAPPORT RENDEZ_VOUS</title>
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
            ETAT CONSULTATION
        </h4>

        {{-- Affichage du filtre actif sur une seule ligne avec Bootstrap --}}
        <div class="d-flex text-lg-center text-uppercase fw-bold justify-content-center flex-wrap gap-3 mb-3" style="font-size: 10px; color: #555;">
            @if(!empty($data['periode']))
                <div><strong>Période:</strong> {{ $data['periode']['du'] }} au {{ $data['periode']['au'] }}</div>
            @endif

            @if(!empty($data['filtre']['consultant_id']))
                <div>
                    <strong>Consultant:</strong>
                    {{ $data['rendezVous']->firstWhere('consultant.id', $data['filtre']['consultant_id'])->consultant->nomcomplet ?? 'N/A' }}
                </div>
            @endif

            @if(!empty($data['filtre']['client_id']))
                <div>
                    <strong>Client:</strong>
                    {{ $data['rendezVous']->firstWhere('client.id', $data['filtre']['client_id'])->client->nomcomplet_client ?? 'N/A' }}
                </div>
            @endif

                @if(!empty($data['filtre']['type']))
                    <div>
                        <strong>Type de consultation:</strong>
                        {{ $data['rendezVous']->first()?->prestation?->type_label ?? $data['filtre']['type'] }}
                    </div>
                @endif
        </div>




        <div class="mt-4">
        <table class="table table-striped table-bordered" style="font-size: 8px;">
            <thead>
            <tr>
                <th scope="col">Code</th>
                <th scope="col">Date</th>
                <th scope="col">Heure</th>
                <th scope="col">Consultant</th>
                <th scope="col">Client</th>
                <th scope="col">Sexe client</th>
                <th scope="col">Tel client</th>
                <th scope="col">Type de consultation</th>
                <th scope="col">Assuré ?</th>
                <th scope="col">Nom assurance</th>
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

                    <td>
                        @if ($rdv->client->assure_pa_cli)
                            Oui
                        @else
                            Non
                        @endif
                    </td>

                    <td>
                        {{ $rdv->client->nom_assure_principale_cli ?? 'RAS' }}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>


</div>


</body>
</html>
