<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>FEUILLE DE ROUTE JOURNALIERE DE CAISSE</title>

    <style>
        {!! $bootstrap !!}
    </style>

    <style>
        @page {
            size: A5 landscape;
            /* Réduction de la marge globale de la page pour gagner de la place */
            margin: 5mm 7mm;
        }

        body, html {
            margin: 0;
            padding: 0;
            font-size: 2.8mm !important; /* Légère réduction pour sécuriser le format A5 */
            font-family: "Times New Roman", serif;
            background-color: #fff;
        }

        .print-wrapper {
            width: 100%;
        }

        h1 {
            font-size: 4.5mm !important;
            margin-top: 2mm;
            margin-bottom: 2mm;
        }

        h2 {
            font-size: 3.5mm !important;
            margin-bottom: 2mm;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            /* Permet de laisser le tableau ajuster la largeur de ses colonnes */
            table-layout: auto;
            page-break-inside: auto;
        }

        /* Suppression des paddings Bootstrap trop grands pour le A5 */
        .table-sm th, .table-sm td {
            padding: 3px 4px !important;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
        }

        .print-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 6mm;
            text-align: center;
            font-size: 2.5mm;
        }

        img {
            max-height: 45px;
            width: auto;
        }

        /* Séparateur visuel fort entre les blocs de prestations */
        .category-delimiter {
            border-right: 1.5px solid #000 !important;
        }
    </style>
</head>
<body>

<div class="col-lg-12 col-sm-12 p-0 print-wrapper">

    <header class="d-flex align-items-center size" style="font-family: 'Times New Roman', serif">
        <div style="width: 20%">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path($logo))) }}" alt="Logo">
        </div>

        <div class="text-center flex-grow-1" style="line-height: 14px; width: 80%;">
            <div class="fs-5 text-uppercase fw-bold">
                {{ $centre->name }}
            </div>
            <div style="font-size: 2.5mm;">
                - {{ $centre->address }} - {{ $centre->town }}
            </div>
            <div style="font-size: 2.5mm;">
                BP: {{ $centre->postal_code }} {{ $centre->town }} -
                Tél. {{ $centre->tel }} {{ $centre->tel2 ? '/' . $centre->tel2 : '' }}
                {{ $centre->fax ? '/ Fax: ' . $centre->fax : '' }}
            </div>
            <div style="font-size: 2.5mm;">
                Email: {{ $centre->email }} | Autorisation n° {{ $centre->autorisation }} | NIU: {{ $centre->contribuable }}
            </div>
        </div>
    </header>

    <div class="mt-1 w-100" style="border-top: 1px double rgba(0, 0, 0, 0.75); margin-bottom: 2px"></div>
    <div class="mb-1 w-100" style="border-top: 1px double rgba(0, 0, 0, 0.75);"></div>

    <h1 class="fw-bold text-center text-uppercase border-bottom pb-1">
        FEUILLE DE ROUTE JOURNALIERE DE CAISSE
    </h1>
    <p class="fst-italic text-end m-0" style="font-size: 2.5mm;">Date d'impression: {{ now()->format('d/m/Y H:i') }}</p>

    @if(isset($start) && isset($end))
        <h2 class="fw-bold text-center text-uppercase m-0">
            <strong>Période :</strong>
            du {{ \Carbon\Carbon::parse($start)->format('d/m/Y') }}
            au {{ \Carbon\Carbon::parse($end)->format('d/m/Y') }}
        </h2>
    @endif

    <div class="mt-2 w-100">
        @php
            $categories = collect($result)->flatMap(function ($caissier) {
                return array_keys($caissier['prestations'] ?? []);
            })->unique()->values()->toArray();

            $modes = collect($result)->flatMap(function ($caissier) {
                return collect($caissier['prestations'] ?? [])->flatMap(function ($prestation) {
                    return is_array($prestation['modes']) ? array_keys($prestation['modes']) : [];
                });
            })->unique()->values()->toArray();

            if (empty($modes)) {
                $modes = ['CAISSE', 'OM', 'MTN'];
            }

            $modesCount = count($modes);

            $colTotals = [];
            foreach($categories as $cat) {
                foreach($modes as $mode) {
                    $colTotals[$cat][$mode] = 0;
                }
            }
            $grandTotalGlobal = 0;
        @endphp

        <table class="table table-bordered table-striped table-sm border-dark text-center text-uppercase">
            <thead>
            <tr class="table-secondary border-dark">
                <th rowspan="2" class="align-middle text-start" style="min-width: 80px;">CAISSIER</th>
                @foreach($categories as $cat)
                    <th colspan="{{ $modesCount }}" class="category-delimiter text-capitalize">
                        {{ $cat }}
                    </th>
                @endforeach
                <th rowspan="2" class="align-middle" style="min-width: 80px;">TOTAL GENERAL</th>
            </tr>

            <tr class="table-light border-dark" style="font-size: 8px;">
                @foreach($categories as $cat)
                    @foreach($modes as $mode)
                        <th class="{{ $loop->last ? 'category-delimiter' : '' }}">
                            {{ $mode }}
                        </th>
                    @endforeach
                @endforeach
            </tr>
            </thead>

            <tbody>
            @foreach($result as $caissier)
                <tr>
                    <td class="text-start fw-bold text-uppercase bg-light">
                        {{ $caissier['user_name'] }}
                    </td>

                    @foreach($categories as $cat)
                        @foreach($modes as $mode)
                            @php
                                $montant = $caissier['prestations'][$cat]['modes'][$mode] ?? 0;
                                $colTotals[$cat][$mode] += $montant;
                            @endphp
                            <td class="{{ $montant > 0 ? 'fw-bold' : 'text-muted' }} {{ $loop->last ? 'category-delimiter' : '' }}">
                                {{ $montant > 0 ? \App\Helpers\FormatPrice::format($montant) : '' }}
                            </td>
                        @endforeach
                    @endforeach

                    @php $grandTotalGlobal += $caissier['total_general']; @endphp
                    <td class="table-secondary fw-bold">
                        {{ \App\Helpers\FormatPrice::format($caissier['total_general']) }}
                    </td>
                </tr>
            @endforeach
            </tbody>

            <tfoot>
            <tr class="table-secondary border-dark fw-bold text-black">
                <td class="text-start text-uppercase">TOTAL</td>
                @foreach($categories as $cat)
                    @foreach($modes as $mode)
                        <td class="{{ $loop->last ? 'category-delimiter' : '' }}">
                            {{ $colTotals[$cat][$mode] > 0 ? \App\Helpers\FormatPrice::format($colTotals[$cat][$mode]) : '' }}
                        </td>
                    @endforeach
                @endforeach
                <td class="bg-dark text-white text-center font-monospace">
                    {{ \App\Helpers\FormatPrice::format($grandTotalGlobal) }}
                </td>
            </tr>
            </tfoot>
        </table>
    </div>
</div>
</body>
</html>
