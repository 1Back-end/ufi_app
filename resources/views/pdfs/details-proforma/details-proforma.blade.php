<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>ETATS JOURNALIERS DES ASSURANCES</title>

    <style>
        {!! $bootstrap !!}
    </style>

    <style>
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


    <div class="d-flex flex-column">

        <!-- Titre centré -->
        <div class="text-center mb-3">
            <h1 class="fs-3 fw-bold text-uppercase">FACTURE PROFORMA</h1>
        </div>

        <!-- Ligne infos : code à gauche, date proforma au centre, date impression à droite -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <!-- Code proforma à gauche -->
            <div class="text-start">
                <span>Code proforma:</span>
                <span class="fw-bold fs-5">{{ $proforma->code }}</span>
            </div>

            <!-- Date proforma au centre -->
            <div class="text-center fst-italic">
                <span>Date proforma:</span>
                <span>{{ \Carbon\Carbon::parse($proforma->created_at)->format('d/m/Y') }}</span>
            </div>

            <!-- Date impression à droite -->
            <div class="text-end fst-italic">
                <span>Date d'impression:</span>
                <span>{{ now()->format('d/m/Y H:i') }}</span>
            </div>
        </div>

        <!-- Nom patient en bas -->
        <div class="d-flex justify-content-start mt-1">
            <span class="d-flex gap-1 align-items-center">
              <span>Nom patient:</span>
              <span class="text-uppercase">{{ $proforma->client->fullname }}</span>
            </span>
        </div>

    </div>


    <hr class="border border-2 border-primary opacity-75">

    <div class="mt-2 w-75">
        <table class="table table-bordered table-striped text-center border-black">
            <thead>
            <tr>
                @if($proforma->type == 2)
                    <th>Consultations demandées</th>
                @elseif($proforma->type == 1)
                    <th>Examens demandés</th>
                @endif
                 @if($proforma->type == 3)
                        <th>Actes demandés</th>
                    @endif
                <th>PU</th>

                @if($proforma->type == 1 || $proforma->type == 3)
                    <th>B</th>
                @endif
            </tr>
            </thead>
            <tbody>
            @foreach ($proforma->items as $index => $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td>{{ \App\Helpers\FormatPrice::format($item->unit_price) }}</td>
                    @if($proforma->type == 1 || $proforma->type == 3)
                    <td>{{ $item->b_value }}</td>
                    @endif
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>


    <div class="col-12 px-1 d-flex justify-content-end mt-5">
        <div class="table-responsive-md" style="width: 400px;">
            <table class="table table-bordered text-center table-striped border-dark">
                <tbody>
                <tr>
                    <th class="text-start">Montant Total HT</th>
                    <td>{{ \App\Helpers\FormatPrice::format($proforma->items->sum('unit_price')) }}</td>
                </tr>
                @if($proforma->type == 1)
                <tr>
                    <th class="text-start">Montant prélèvement</th>
                    <td>{{ \App\Helpers\FormatPrice::format($proforma->price_kb_prelevement) }}</td>
                </tr>
                @endif
                <tr>
                    <th class="text-start">Total Général</th>
                    <td>{{ \App\Helpers\FormatPrice::format($proforma->total) }}</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>




</div>


</body>
</html>
