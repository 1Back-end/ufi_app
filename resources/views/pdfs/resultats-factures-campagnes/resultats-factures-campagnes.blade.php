<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>RESULTATS EXAMENS CAMPAGNES</title>

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
            <h1 class="fs-3 fw-bold text-uppercase">BULLETIN D'EXAMEN</h1>
        </div>

        <!-- Ligne infos : code à gauche, date campagne au centre, date prélèvement à droite -->
        <div class="d-flex justify-content-between align-items-start mb-3">
            <!-- Code dossier à gauche -->
            <div class="text-start">
                <span>Code dossier :</span>
                <span class="fw-bold fs-5">{{ $resultat_facture_campagne->reference }}</span>
            </div>

            <!-- Date campagne au centre avec date prélèvement en dessous -->
            <div class="text-center fst-italic">
                <div>
                    <span>Date campagne :</span>
                    <span>
                        Du {{ \Carbon\Carbon::parse($resultat_facture_campagne->factureCampagne?->campagne?->start_date)->format('d/m/Y') }}
                    au {{ \Carbon\Carbon::parse($resultat_facture_campagne->factureCampagne?->campagne?->end_date)->format('d/m/Y') }}
            </span>
                </div>
                <div class="mt-1">
                    <span>Date prélèvement :</span>
                    <span>
                {{ $resultat_facture_campagne->prelevement_date ? \Carbon\Carbon::parse($resultat_facture_campagne->prelevement_date)->format('d/m/Y') : '---' }}
            </span>
                </div>
            </div>

            <!-- Date d'impression à droite -->
            <div class="text-end fst-italic">
                <span>Date d'impression :</span>
                <span>{{ now()->format('d/m/Y H:i') }}</span>
            </div>
        </div>

        <!-- Nom patient en bas -->
        <div class="d-flex justify-content-start mt-1">
        <span class="d-flex gap-1 align-items-center">
            <span>Nom patient :</span>
            <span class="text-uppercase">{{ $resultat_facture_campagne->patient->fullname }}</span>
        </span>
        </div>

    </div>



    <hr class="border border-2 border-primary opacity-75">

    <div class="col-8 px-1 mt-3">
        <div class="table-responsive-md">
        <table class="table table-bordered mt-2 border-dark">
            <thead>
            <tr>
                <th colspan="2" class="text-center py-3" style="border-style: dotted">
                    <strong>
                        {{ $resultat_facture_campagne->factureCampagne->campagne->full_name }}
                    </strong>
                </th>
            </tr>
            <tr>
                <th>Examens demandés</th>
                <th>Résultats</th>
            </tr>
            </thead>
            <tbody>
            @forelse($resultat_facture_campagne->factureCampagne->campagne->elements as $element)
                @if($element->type === 'examens' && $element->element)
                    @php
                        // Transformer le tableau examens en collection
                        $examensCollection = collect($resultat_facture_campagne->examens ?? []);

                        // Chercher le résultat correspondant
                        $resultat = $examensCollection->firstWhere('id', $element->element->id);
                    @endphp
                    <tr>
                        <td>{{ $element->element->name ?? '—' }}</td>
                        <td>
                            @if($resultat)
                                @if($resultat['result'] === true)
                                    <span class="text-uppercase text-success">Positif</span>
                                @elseif($resultat['result'] === false)
                                    <span class="text-uppercase text-danger">Négatif</span>
                                @else
                                    <span class="badge bg-secondary">—</span>
                                @endif
                            @else
                                <span class="badge bg-secondary">—</span>
                            @endif
                        </td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="2" class="text-center">Aucun examen trouvé</td>
                </tr>
            @endforelse
            </tbody>


        </table>
    </div>


    </div>
</div>

</body>
</html>
