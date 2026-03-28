@php use Carbon\Carbon; @endphp
    <!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>ETATS DE CAISSES</title>

    <style>
        {!! $bootstrap !!}
    </style>

    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
            counter-reset: page;
        }

        body, html {
            height: 100%;
            margin: 0;
            padding: 0;
            font-size: 3mm !important;
            font-family: "Times New Roman", serif;
        }

        .print-wrapper {
            position: relative;
        }

        .print-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 10mm;
            text-align: center;
        }

        .page-number:before {
            content: "Page " counter(page) " / " counter(pages);
        }

        h1 {
            font-size: 5mm !important;
        }

        table {
            page-break-inside: auto;
            width: 100%;
        }

        thead {
            display: table-header-group; /* Garde l'en-tête sur chaque page */
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        img {
            width: auto;
            height: auto;
        }
    </style>

</head>
<body>

<div class="col-lg-12 col-sm-12 p-0 print-wrapper">


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


    <div class="mt-2 w-100" style="border-top: 1px double rgb(0, 0, 0, 0.75); margin-bottom: 2px"></div>
    <div class="mb-2 w-100" style="border-top: 1px double rgb(0, 0, 0, 0.75);"></div>


    @php
        $caisse = $result->first()->caisse ?? null;
    @endphp
    <h1 class="fs-3 fw-bold text-center text-uppercase">
        ETAT DE LA  {{ $caisse->name ?? '-' }}
        GÉRÉE PAR {{ $caisse->user->nom_utilisateur ?? '-' }}
    </h1>

    <p class="fst-italic text-end">Date d'impression: {{ now()->format('d/m/Y H:i') }}</p>

    @if(isset($start) && isset($end))
    <h2 class="fw-bold text-center fs-5 text-uppercase">
        <strong>Période :</strong>
        du {{ \Carbon\Carbon::parse($start)->format('d/m/Y') }}
        au {{ \Carbon\Carbon::parse($end)->format('d/m/Y') }}
    </h2>
    @endif


    <div class="mt-2 w-100">
        <table class="table table-bordered table-striped" style="font-size: 12px;">
            <thead>
            <tr>
                <th>#</th>
                <th>N° facture</th>
                <th>N° règlement</th>
                <th>Montant</th>
                <th>Mode de règlement</th>
                <th>Date création</th>
                <th>Etat</th>
            </tr>
            </thead>
            <tbody>
            @forelse($result as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->facture->code ?? '-' }}</td>
                    <td>{{ $item->regulation_id ?? '-' }}</td>
                    <td>{{ number_format($item->montant, 0, ',', ' ') }} FCFA</td>
                    <td>{{ $item->regulation_method->name ?? '-' }}</td>
                    <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i:s') }}</td>
                    <td class="text-danger">
                        @if(!$item->is_deleted)
                        @else
                            Annulée
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center">Aucune donnée trouvée</td>
                </tr>
            @endforelse

            </tbody>


        </table>
    </div>
    </div>
</body>
</html>
