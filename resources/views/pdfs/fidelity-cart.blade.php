<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Carte de fidélité</title>

    <style>
        {!! $bootstrap !!}
    </style>

    <style>
        * {
            margin: 0;
            padding: 0;
        }

        @font-face {
            font-family: 'Oswald';
            src: url({{ storage_path("fonts/Oswald.ttf") }}) format("truetype");
            font-weight: 600;
            font-style: normal;
        }

        body {
            font-family: "Oswald", "Helvetica", serif;
            margin: 0;
            padding: 0;
            position: relative;
            min-height: 100vh;
            background: center / cover no-repeat url("data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/background-fidelity-card.jpg'))) }}");
        }

        .container {
            width: 90%;
            margin: 0 auto;
            padding: 20px;
        }

        .title-section {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo-container {
            text-align: right;
            margin-right: 30px;
            position: absolute;
            right: 20px;
            top: 15px;
        }

        .logo-container img {
            height: auto;
            width: 90px;
        }

        .content-table {
            width: 100%;
            text-align: center;
            margin: 40px 0;
        }

        .content-table td {
            padding: 5px;
            font-size: 0.9em;
            font-weight: bold;
        }

        .barcode-img {
            max-width: 250px;
            height: auto;
        }

        .footer {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 0.5em;
        }

        .footer div {
            margin-top: 5px;
        }
    </style>
</head>
<body>
<div class="container">

    <!-- Titre centré sur toute la largeur -->
    <div class="title-section">
        <span style="font-size: 0.7em">{{ \Illuminate\Support\Str::upper('Carte de fidélité') }}</span><br>
        <span style="font-size: 1.4em; font-weight: bold; color: #0046aa;">{{ \Illuminate\Support\Str::upper($centre->name) }}</span>
    </div>

    <!-- Logo à droite -->
    @if($logo)
        <div class="logo-container">
            <img src="data:{{ $mimetype }};base64,{{ $logo }}" alt="Logo">
        </div>
    @endif

    <!-- Contenu principal -->
    <table class="content-table">
        <tr>
            <td style="font-size: 1.8em; font-weight: bold; color: #1e9178;">{{ \Illuminate\Support\Str::upper($client->nomcomplet_client) }}</td>
        </tr>
        <tr>
            <td>
                <span class="d-flex flex-column justify-content-center gap-1">
                    <span class="text-center ">
                        {{ QrCode::size(100)->generate($client->ref_cli) }}
                    </span>
                    <span class="fst-italic fw-bold">{{ $client->ref_cli }}</span>
                </span>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <div class="footer">
        <p>Cette carte donne droit à une consultation gratuite à chaque 10ième consultation.</p>
        <div>
            <span style="margin-right: 10px">VALIDE JUSQU'AU : {{ now()->locale('fr')->addDays($validity)->isoFormat('LL') }}</span>
            <span style="font-style: italic">Appeler ce numéro <strong>{{ $centre?->tel }}</strong> si vous retrouvez cette carte égarée.</span>
        </div>
    </div>

</div>
</body>
</html>
