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
        ETAT DE LA {{ $caisse->name ?? '-' }}
        GÉRÉE PAR {{ $caisse->user->nom_utilisateur ?? '-' }}
    </h1>

    <h5 class="fs-4 fw-bold text-center text-uppercase" style="margin-top: 20px;">
        OUVERTURE :
        <span style="color: #007bff;">
        {{ $session ? \App\Helpers\FormatPrice::format($session->fonds_ouverture) : '0' }}
    </span>

        <span style="margin: 0 15px;">|</span>

        @if($session && $session->etat === 'FERMEE')
            FERMETURE :
            <span style="color: #dc3545;">
            {{ \App\Helpers\FormatPrice::format($session->fonds_fermeture) }}
        </span>
        @else
            SOLDE ACTUEL :
            <span style="color: #28a745;">
            {{ $session ? \App\Helpers\FormatPrice::format($session->solde) : '0' }}
        </span>
        @endif
    </h5>

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
            <tr class="bg-dark text-white">
                <th>#</th>
                <th>N° facture</th>
                <th>Patient</th>
                <th>Montant facture</th>
                <th>Règlement</th>
                <th>Mode</th>
                <th>Date</th>
                <th>Etat</th>
            </tr>
            </thead>
            <tbody>
            @php
                $total_facture_global = 0;
                $total_encaisse_global = 0;
            @endphp

            @forelse($result->groupBy('facture_id') as $facture_id => $items)
                @php
                    $facture = $items->first()->facture;
                    $montant_facture = ($facture->amount_client ?? 0) + ($facture->amount_pc ?? 0);
                    $montant_encaisse_facture = $items->where('is_deleted', false)->sum('montant');
                    $reste_facture = max($montant_facture - $montant_encaisse_facture, 0);

                    // Cumul pour le footer hors tableau
                    $total_facture_global += $montant_facture;
                    $total_encaisse_global += $montant_encaisse_facture;
                @endphp

                <tr style="background-color: #f8f9fa;">
                    <th colspan="8" class="py-2 px-3" style="border-left: 4px solid #333;">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>
                                <strong>FACTURE N°{{ $facture->code ?? '-' }}</strong>
                            </span>
                            <span>
                                Total: <strong>{{ \App\Helpers\FormatPrice::format($montant_facture) }}</strong> |
                                Encaissé: <strong class="text-success">{{ \App\Helpers\FormatPrice::format($montant_encaisse_facture) }}</strong> |
                                Reste: <strong class="{{ $reste_facture > 0 ? 'text-danger' : 'text-dark' }}">{{ \App\Helpers\FormatPrice::format($reste_facture) }}</strong>
                            </span>
                        </div>
                    </th>
                </tr>

                @foreach($items as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $facture->code ?? '-' }}</td>
                        <td>{{ $facture->prestation->client->nomcomplet_client ?? '-' }}</td>
                        <td class="font-weight-bold">{{ \App\Helpers\FormatPrice::format($montant_facture) }}</td>
                        <td class="font-weight-bold">{{ \App\Helpers\FormatPrice::format($item->montant) }}</td>
                        <td>{{ $item->regulation_method->name ?? '-' }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i') }}</td>
                        <td>
                            <span class="badge {{ $item->is_deleted ? 'badge-danger' : 'badge-success' }}">
                                {{ $item->is_deleted ? 'Annulée' : '' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="8" class="text-center py-4">Aucune donnée trouvée</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card border-dark shadow-sm">
                    <div class="card-body p-3 text-center">
                        <h6 class="text-muted uppercase small font-weight-bold">Total Factures</h6>
                        <h3 class="mb-0">{{ \App\Helpers\FormatPrice::format($total_facture_global) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-success shadow-sm">
                    <div class="card-body p-3 text-center">
                        <h6 class="text-success uppercase small font-weight-bold">Total Encaissé</h6>
                        <h3 class="mb-0 text-success">{{ \App\Helpers\FormatPrice::format($total_encaisse_global) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                @php $reste_global = max($total_facture_global - $total_encaisse_global, 0); @endphp
                <div class="card border-danger shadow-sm">
                    <div class="card-body p-3 text-center">
                        <h6 class="text-danger uppercase small font-weight-bold">Reste à Recouvrer</h6>
                        <h3 class="mb-0 text-danger">{{ \App\Helpers\FormatPrice::format($reste_global) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
