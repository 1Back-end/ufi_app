<!doctype html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

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
{{-- Header --}}
@php
    try {
@endphp

<header class="d-flex align-items-center size" style="font-family: 'Times New Roman', serif">
    <div class="w-25">
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path($logo))) }}" alt=""
             class="img-fluid w-50">
    </div>

    <div class="text-center" style="line-height: 18px">
        <div class="fs-3 text-uppercase fw-bold">
            {{ $centre->name }}
        </div>

        <div class="">
            - {{ $centre->address }} - {{ $centre->town }}
        </div>

        <div class="">
            BP: {{ $centre->postal_code }} {{ $centre->town }} -
            Tél. {{ $centre->tel }} {{ $centre->tel2 ? '/' . $centre->tel2 : '' }}
            / Fax: {{ $centre->fax ?? '' }}
        </div>

        <div class="">
            Email: {{ $centre->email }}
        </div>

        <div class="">
            Autorisation n° {{ $centre->autorisation }}
            NIU: {{ $centre->contribuable }}
        </div>
    </div>
</header>

<div class="mt-1 w-100" style="border-top: 1px double rgb(0, 0, 0, 0.75); margin-bottom: 2px"></div>
<div class="mb-1 w-100" style="border-top: 1px double rgb(0, 0, 0, 0.75);"></div>

<div class="">
    <div class="d-flex justify-content-between">
        <div class="" style="font-family: Arial, serif">
            <table>
                <tbody>
                <tr>
                    <td class="">Dossier patient:</td>
                    <td class="ps-3 fw-bold">{{ $resultat_facture_campagne->reference }}</td>
                </tr>

                <tr>
                    <td class="">Nom du patient:</td>
                    <td class="ps-3 fw-bold">{{ $resultat_facture_campagne->patient->fullname }}</td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="" style="width: 35%">
            <div class="d-flex gap-2 align-items-center text-start">
                <div class="fst-italic">Date d'impression:</div>
                <div class="">
                    {{ now()->format('d/m/Y H:i') }}
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 text-start">
                <div class="fst-italic">Période Campagne :</div>
                <div class="">
                    Du {{ \Carbon\Carbon::parse($resultat_facture_campagne->factureCampagne?->campagne?->start_date)->format('d/m/Y') }}
                    au {{ \Carbon\Carbon::parse($resultat_facture_campagne->factureCampagne?->campagne?->end_date)->format('d/m/Y') }}
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between fw-bold size">
        <span>&nbsp;</span>

    </div>

    <table id="results" class="table border border-black mb-0" style="border-color: rgb(0, 0, 0, 0.5)">
        <thead>
        <tr style="background-color: #F1F5F9;">
            <th colspan="3" class="text-center py-2 text-primary" style="border-bottom: 2px solid #CBD5E1;">
                <strong style="font-size: 3mm;">
                    {{ $resultat_facture_campagne->factureCampagne->campagne->full_name }}
                </strong>
            </th>
        </tr>
        <tr>
            <th style="background-color: #ccc; padding: 2px; width: 10%;" class="text-center" scope="col">N°</th>
            <th style="background-color: #ccc; padding: 2px; width: 60%;" class="text-start" scope="col">Examens demandés</th>
            <th style="background-color: #ccc; padding: 2px; width: 30%;" class="text-center" scope="col">Résultat</th>
        </tr>
        </thead>
        <tbody>
        @php $index = 1; @endphp
        @forelse($resultat_facture_campagne->factureCampagne->campagne->elements as $element)
            @if($element->type === 'examens' && $element->element)
                @php
                    $examensCollection = collect($resultat_facture_campagne->examens ?? []);
                    $resultat = $examensCollection->firstWhere('id', $element->element->id);
                @endphp
                <tr>
                    <td class="text-center fw-medium text-secondary">{{ $index++ }}</td>
                    <td class="fw-semibold text-dark text-start">{{ $element->element->name ?? '—' }}</td>
                    <td class="text-center">
                        @if($resultat)
                            @if($resultat['result'] === true || $resultat['result'] === 'true')
                                <span class="badge-status text-danger fw-bold">Positif</span>
                            @elseif($resultat['result'] === 'weakly_positive')
                                <span class="badge-status text-warning fw-bold" style="color: #d97706 !important;">Faiblement Positif</span>
                            @elseif($resultat['result'] === false || $resultat['result'] === 'false')
                                <span class="badge-status text-success fw-bold">Négatif</span>
                            @else
                                <span class="status-empty">En attente</span>
                            @endif
                        @else
                            <span class="status-empty"></span>
                        @endif
                    </td>
                </tr>
            @endif
        @empty
            <tr>
                <td colspan="3" class="text-center py-3 text-muted">Aucun examen trouvé</td>
            </tr>
        @endforelse
        </tbody>
    </table>

</div>

@php
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Erreur PDF complète', [
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'trace' => $e->getTraceAsString()
        ]);
    }
@endphp
</body>

</html>
