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


    <h1 class="fs-3 fw-bold text-center text-uppercase">
        PLANNING DES CONSULTANTS INTERNES ET SUR RENDEZ-VOUS
    </h1>


    <p class="text-end small">Date d'impression : {{ now() }}</p>

    <div class="card shadow-sm border-0 mt-3">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm" style="font-size: 12px;">
                <thead class="bg-dark text-white">
                <tr>
                    <th style="width: 5%">#</th>
                    <th style="width: 20%">Nom complet</th>
                    <th style="width: 15%">Téléphone</th>
                    <th style="width: 20%">Spécialité</th>
                    <th style="width: 10%">Type</th>
                    <th style="width: 30%">Disponibilités</th>
                </tr>
                </thead>
                <tbody>
                @foreach($consultants as $consultant)
                    @if($consultant->disponibilites->isNotEmpty())
                        <tr>
                            <td class="text-center fw-bold">{{ $consultant->id }}</td>
                            <td>
                                <div class="fw-bold text-dark text-uppercase">{{ $consultant->nomcomplet }}</div>
                            </td>
                            <td>
                                <span class="text-nowrap"><i class="bi bi-telephone text-muted me-1"></i>{{ $consultant->tel }}</span>
                            </td>
                            <td>
                                <span class="text-muted">{{ $consultant->specialite?->nom_specialite }}</span>
                            </td>
                            <td>
                           <span class="text-primary fst-italic text-capitalize">
                                {{ $consultant->type }}
                            </span>
                            </td>
                            <td class="pe-3">
                                @php
                                    $disposGrouped = [];
                                    foreach($consultant->disponibilites as $d) {
                                        $jourNom = match((int) $d->jour) {
                                            1 => 'Lundi',
                                            2 => 'Mardi',
                                            3 => 'Mercredi',
                                            4 => 'Jeudi',
                                            5 => 'Vendredi',
                                            6 => 'Samedi',
                                            7 => 'Dimanche',
                                            default => 'Inconnu',
                                        };

                                        // Sécurité pour le formatage de l'heure (Carbon ou String)
                                        $debut = $d->heure_debut instanceof \Carbon\Carbon ? $d->heure_debut->format('H:i') : substr($d->heure_debut, 0, 5);
                                        $fin = $d->heure_fin instanceof \Carbon\Carbon ? $d->heure_fin->format('H:i') : substr($d->heure_fin, 0, 5);

                                        $disposGrouped[$jourNom][] = $debut . ' - ' . $fin;
                                    }
                                @endphp

                                <div class="d-flex flex-column gap-2 my-1">
                                    @foreach($disposGrouped as $jour => $creneaux)
                                        <div class="d-flex align-items-center flex-wrap">
                                            <span class="text-primary fst-italic text-capitalize me-3" style="font-size: 11px;">
                                                {{ $jour }}
                                            </span>
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($creneaux as $creneau)
                                                    <span class="badge bg-light text-dark border px-2 py-1 fw-normal" style="font-size: 11px;">
                                                <i class="bi bi-clock text-muted me-1" style="font-size: 10px;"></i>{{ $creneau }}
                                            </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>
    </div>


</div>


</body>
</html>
