<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport Rendez vous</title>

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

    <p class="mb-2"><strong>Noms(s) et Prénom(s) :</strong> {{ $rapport->rendezVous->client->nomcomplet_client ?? 'RAS' }}</p>
    <div class="mb-2"><strong>Âge :</strong> {{ $rapport->rendezVous->client->age ?? 'RAS' }} ans</div>
    <div class="mb-2"><strong>Sexe :</strong> {{ $rapport->rendezVous->client->sexe->description_sex ?? 'RAS' }}</div>
    <div class="mb-2"><strong>Tel :</strong> {{ $rapport->rendezVous->client->tel_cli ?? 'RAS' }}</div>
    <div class="mb-2"><strong>Renseignement(s) clinique(s) :</strong> {{ $rapport->rendezVous->client->renseign_clini_cli ?? 'RAS' }}</div>

    <p class="mb-2">
        <strong>Médecin Traitant :</strong>
        {{ $rapport->rendezVous->consultant->nomcomplet ?? 'RAS' }}
    </p>
    <p class="mb-2">
        <strong>Examen demandé :</strong>
        {{ $rapport->prestation->type_label ?? 'RAS' }}
    </p>

    <fieldset class="mb-lg-3 mt-3 p-2 border border-1 rounded border-dark">
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
</div>

</body>
</html>
