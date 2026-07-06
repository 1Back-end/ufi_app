@php use Carbon\Carbon; @endphp
    <!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>ETATS DES PRESTATIONS SUPPRIMES</title>

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
                - {{ $centre->address }}
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


    <h4 class="fs-3 fw-bold text-center text-uppercase my-3">
        État des prestations supprimées
    </h4>

    <p class="fst-italic text-end text-muted small">
        Date d'impression : {{ now()->format('d/m/Y H:i') }}
    </p>

    @if(!empty($start) && !empty($end))
        <h5 class="fw-bold text-center fs-5 text-uppercase mb-4">
            Période :
            <span class="fw-normal">du {{ \Carbon\Carbon::parse($start)->format('d/m/Y') }}</span>
            <span class="fw-normal">au {{ \Carbon\Carbon::parse($end)->format('d/m/Y') }}</span>
        </h5>
    @endif

    <div class="mt-2 w-100">
        <table class="table table-bordered table-sm align-middle" style="font-size: 10px;">
            <thead>
            <tr class="bg-dark text-white text-center">
                <th style="width: 4%;">#</th>
                <th>Type</th>
                <th>Patient</th>
                <th>Consultant</th>
                <th>Date Prestation</th>
                <th>Créé par</th>
                <th>Modifié par</th>
                <th>Supprimé le</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($result as $index => $prestation)
                <!-- Ligne principale : Les données de la prestation -->
                <tr class="table-light">
                    <td class="fw-bold text-center bg-secondary text-white">{{ $index + 1 }}</td>
                    <td class="fw-bold text-primary">{{ $prestation->type_label }}</td>
                    <td>{{ $prestation->client?->nomcomplet_client ?? 'N/A' }}</td>
                    <td>{{ $prestation->consultant?->nomcomplet ?? 'N/A' }}</td>
                    <td class="text-center">{{ $prestation->created_at ? \Carbon\Carbon::parse($prestation->created_at)->format('d/m/Y H:i') : '-' }}</td>
                    <td>{{ $prestation->createdBy?->nom_utilisateur ?? 'N/A' }}</td>
                    <td>{{ $prestation->updatedBy?->nom_utilisateur ?? 'N/A' }}</td>
                    <td class="text-center text-danger fw-bold">{{ $prestation->deleted_at ? \Carbon\Carbon::parse($prestation->deleted_at)->format('d/m/Y H:i') : '-' }}</td>
                </tr>

                <tr>
                    <td colspan="8" class="bg-white px-3 py-2">
                        <span class="text-dark fs-5">
                            <i class="fas fa-comment-alt text-warning me-1"></i>
                            <strong>Motif de suppression :</strong>
                            {{ $prestation->reason_for_delete ?? 'Aucun motif renseigné.' }}
                        </span>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
