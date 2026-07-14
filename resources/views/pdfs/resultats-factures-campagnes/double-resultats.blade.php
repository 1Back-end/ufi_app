<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        table tbody {
            font-size: 3mm !important;
        }

        img {
            width: auto;
            height: auto;
        }

        #results {
            /* Ceci est crucial pour que les largeurs de colonne fonctionnent comme des largeurs max */
            table-layout: fixed;
            width: 100%;
            /* S'assurer que le tableau utilise l'espace disponible */
        }

        /* Appliquer la largeur souhaitée à la colonne Résultat */
        /* (Vous devrez cibler la 2e colonne de votre tableau) */
        #results th:nth-child(2),
        #results td:nth-child(2) {
            width: 40%;
            /* Ou une valeur fixe en pixels, par exemple: 150px */
            word-wrap: break-word;
            /* Force le passage à la ligne pour les mots trop longs */
            /* overflow-wrap: break-word; est une alternative moderne */
        }

        /* S'assurer que les autres colonnes ont suffisamment d'espace */
        #results th:nth-child(1),
        #results td:nth-child(1) {
            width: 25%;
            /* Analyse */
        }

        #results th:nth-child(3),
        #results td:nth-child(3) {
            width: 20%;
            /* Antériorités */
        }

        #results th:nth-child(4),
        #results td:nth-child(4) {
            width: 25%;
            /* Valeurs normales */
        }
    </style>
    <title>{{ $filename }}</title>
</head>
<body>
@php
    try {
@endphp

<div class="a4-wrapper">

    <div class="a5-bulletin">
        @include('pdfs.resultats-factures-campagnes._bulletin_content')
    </div>

    <div class="divider"></div>

    <div class="a5-bulletin">
        @include('pdfs.resultats-factures-campagnes._bulletin_content')
    </div>

</div>

@php
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Erreur PDF double', [
            'message' => $e->getMessage(),
            'line' => $e->getLine()
        ]);
    }
@endphp
</body>
</html>
