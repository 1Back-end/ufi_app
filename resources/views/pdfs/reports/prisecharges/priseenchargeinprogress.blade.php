@php use App\Helpers\FormatPrice; @endphp
    <!doctype html>
<html lang="fr">

<head>
    <meta charset="UTF-8">

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

        header {
            font-family: 'Helvetica', serif;
        }

        h1 {
            font-size: 6mm !important;
        }

        table {
            font-size: 3mm !important;
        }

        img {
            width: auto;
            height: auto;
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
    </style>
</head>

<body>
{{-- Header --}}
<header class="row pb-2 border-bottom border-1 border-black">
    <div class="col-4">
        @if($logo)
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path($logo))) }}" alt="">
        @endif
    </div>

    <div class="text-center col text-success">
        <div class="fs-1 text-uppercase">
            {{ $centre->name }}
        </div>

        <hr class="my-1 border border-success border-1 opacity-75 col-12">

        <div class="">
            - {{ $centre->address }} - {{ $centre->town }}
        </div>

        <div class="">
            BP: {{ $centre->postal_code }} {{ $centre->town }} -
            Tél. {{ $centre->tel }} {{ $centre->tel2 ? '/' . $centre->tel2 : '' }}
            / Fax: {{ $centre->fax }}
        </div>

        <div class="">
            Email: {{ $centre->email }}
        </div>

        <div class="">
            Autorisation n° {{ $centre->autorisation }}
            NUI: {{ $centre->contribuable }}
        </div>
    </div>
</header>

<h1 class="fs-3 fw-bold text-center text-uppercase text-decoration-underline">
    LISTE DES PRISES EN CHARGES EN COURS
</h1>

<p class="fst-italic text-end">Date d'impression: {{ now()->format('d/m/Y H:i') }}</p>

<h2 class="fw-bold text-center fs-5">
    Période > {{ now()->format('d/m/Y') }}
</h2>

@foreach($assurances as $assurance)
    <div class="border border-1 border-black p-2 mb-1">
        <p class="d-flex gap-2 fw-bold align-items-center">
            <span>Nom Assurance: </span>
            <span>{{ $assurance->nom }}</span>
        </p>

        <p class="d-flex gap-2 fw-bold align-items-center">
            <span>Adresse: </span>
            <span>{{ $assurance->adresse }}</span>
        </p>
    </div>

    <table class="table table-bordered mb-3">
        <thead>
        <tr>
            <th>Nom patient</th>
            <th>Téléphone</th>
            <th>Date de naissance</th>
            <th>Numéro PC</th>
            <th>Période</th>
            <th>Taux</th>
        </tr>
        </thead>
        <tbody>
            @foreach($assurance->priseEnCharges as $priseEnCharge)
                <tr>
                    <td>{{ $priseEnCharge->client->nomcomplet_client }}</td>
                    <td>
                        <span class="d-flex flex-column">
                            <span>{{ $priseEnCharge->client->tel_cli }}</span>
                            <span>{{ $priseEnCharge->client->tel2_cli }}</span>
                        </span>
                    </td>
                    <td>{{ $priseEnCharge->client->date_naiss_cli }}</td>
                    <td>{{ $priseEnCharge->code }}</td>
                    <td>
                        {{ $priseEnCharge->date_debut->format('d/m/Y') }} - {{ $priseEnCharge->date_fin->format('d/m/Y') }}
                    </td>
                    <td>{{ $priseEnCharge->taux_pc }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endforeach

</body>

</html>
