@php use Carbon\Carbon; @endphp
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

    <title> ÉTAT DES TRANSFERTS DE FONDS DE CAISSE</title>
</head>

<body>
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

    <p class="fst-italic text-end">Date d'impression: {{ now()->format('d/m/Y H:i') }}</p>

    <h2 class="fw-bold text-center fs-5 text-uppercase mb-1">
        ÉTAT DES TRANSFERTS DE FONDS DE CAISSE
        @if(!empty($status))
            - <span class="text-decoration-underline">{{ Str::upper($status) }}</span>
        @endif
    </h2>

    @if(isset($start) && isset($end))
        <h3 class="fw-normal text-center fs-6 text-uppercase mb-3">
            <strong>Période :</strong>
            du {{ Carbon::parse($start)->format('d/m/Y') }}
            au {{ Carbon::parse($end)->format('d/m/Y') }}
        </h3>
    @endif

    <div class="mt-2 w-100">
        <table class="table table-bordered table-striped table-sm" style="font-size: 12px;">
            <thead>
            <tr>
                <th>Code</th>
                <th>Caisse Départ</th>
                <th>Caisse Arrivée</th>
                <th>Transféré par</th>
                <th>Statut</th>
                <th>Montant</th>
                <th>Créé le</th>
                <th>Dernière mise à jour</th>
            </tr>
            </thead>

            <tbody>
            @php $totalGeneral = 0; @endphp
            @forelse($result as $index => $item)
                @php $totalGeneral += $item->montant_send; @endphp
                <tr>
                    <td class="fw-bold">{{ $item->code ?? '' }}</td>
                    <td>{{ $item->caisse_depart?->name ?? '' }}</td>
                    <td>{{ $item->caisse_reception?->name ?? '' }}</td>
                    <td>
                        {{ $item->sender?->nom_utilisateur ?? $item->sender?->nom_utilisateur ?? '' }}
                    </td>
                    <td class="text-center">
                        @if($item->status === 'validated')
                            <span class="badge-validated">Validé</span>
                        @elseif($item->status === 'pending')
                            <span class="badge-pending">En attente</span>
                        @elseif($item->status === 'cancelled')
                            <span class="badge-cancelled">Rejetté</span>
                        @else
                            {{ $item->status }}
                        @endif
                    </td>
                    <td class="text-end fw-bold">
                        {{ \App\Helpers\FormatPrice::format($item->montant_send) }}
                    </td>
                    <td>
                        {{ Carbon::parse($item->created_at)->format('d/m/Y H:i') }}
                        <br>
                        <small class="text-muted">Par: {{ $item->creator?->nom_utilisateur ?? $item->creator?->nom_utilisateur ?? '' }}</small>
                    </td>

                    <td>
                        {{ Carbon::parse($item->updated_at)->format('d/m/Y H:i') }}
                        <br>
                        <small class="text-muted">Par: {{ $item->updater?->nom_utilisateur ?? $item->updater?->nom_utilisateur ?? '' }}</small>
                    </td>
                </tr>
            @empty
                <tr>

                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

</body>
</html>
