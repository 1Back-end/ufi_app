<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>FICHE D'INVENTAIRE - {{ $centre->name ?? '' }}</title>

    <style>
        {!! $bootstrap !!}
    </style>

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
            display: table-header-group;
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

    <h1 class="fs-4 fw-bold text-center text-uppercase my-2">
        FICHE D'INVENTAIRE DES PRODUITS DE L'EMPLACEMENT {{ $emplacement->zone_stockage }}
    </h1>

    <p class="fst-italic text-end mb-2" style="font-size: 9px;">Date d'impression : {{ now()->format('d/m/Y H:i') }}</p>

    <table class="table table-bordered table-striped" style="font-size: 12px;">
        <thead>
        <tr>
            <th style="width: 5%;" class="text-center">#</th>
            <th style="width: 55%;">Produit*</th>
            <th style="width: 20%;" class="text-center">Qté en stock</th>
            <th style="width: 20%;" class="text-center">Qté observée</th>
        </tr>
        </thead>
        <tbody>
        @forelse($products as $index => $lot)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <div class="fw-bold text-uppercase">{{ $lot->produit?->name ?? '' }}</div>
                    <div class="text-muted" style="font-size: 8.5px;">{{ $lot->produit?->ref ?? '' }}</div>
                </td>
                <td class="text-center fw-bold" style="font-size: 12px;">{{ $lot->produit?->total_stock ?? 0 }}</td>
                <td></td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center">Aucun produit trouvé pour cet inventaire</td>
            </tr>
        @endforelse
        </tbody>
    </table>

</div>


</body>
</html>
