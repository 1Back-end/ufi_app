<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tarifaire des soins</title>
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

    <h1 class="fs-3 fw-bold text-center text-uppercase">
        TARIFAIRE DES SOINS DU CENTRE MEDICAL
    </h1>

    <p class="fst-italic text-end">Date d'impression: {{ now()->format('d/m/Y H:i') }}</p>

    @foreach($types as $typeIndex => $type)
        @if($type->soins->count())
            <h6 class="mt-4 mb-xxl-3 text-center text-uppercase">
                <strong>{{ roman_number($typeIndex + 1) }}. {{ $type->name }}</strong>
            </h6>

            <table class="table table-bordered table-striped mt-2" style="font-size: 12px;">
                <thead>
                <tr>
                    <th>N°</th>
                    <th>Soin</th>
                    <th>Prix client</th>
                    <th>Prix assuré</th>
                </tr>
                </thead>
                <tbody>
                @foreach($type->soins as $index => $soin)
                    <tr>
                        <td>{{ $soin->id }}</td>
                        <td>{{ $soin->name }}</td>
                        <td>{{ \App\Helpers\FormatPrice::format($soin->pu) }}</td>
                        <td>{{ \App\Helpers\FormatPrice::format($soin->pu_default) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    @endforeach
</div>
</body>
</html>
