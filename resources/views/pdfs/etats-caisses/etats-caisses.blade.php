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

    <h5 class="fs-6 fw-bold text-center text-uppercase" style="margin-top: 20px;">
        OUVERTURE :
        <span style="color: #007bff;">
        {{ $session ? \App\Helpers\FormatPrice::format($session->fonds_ouverture) : '0' }}
    </span>

        <span class="text-muted" style="margin: 0 10px;">|</span>

        PETITE MONNAIE :
        <span style="color: #007bff;">
        {{ $session ? \App\Helpers\FormatPrice::format($caisse->small_change) : '0' }}
    </span>

        <span class="text-muted" style="margin: 0 10px;">|</span>


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
        <table class="table table-bordered table-striped table-sm" style="font-size: 12px;">
            <thead>
            <tr class="bg-dark text-white">
                <th>#</th>
                <th>N° facture</th>
                <th>Patient</th>
                <th>Montant facture</th>
                <th>Règlement</th>
                <th>Mode</th>
                <th>Référence</th>
                <th>Date</th>
                <th>Reste</th>
            </tr>
            </thead>

            <tbody>

            @php
                $total_facture_global = 0;
                $total_encaisse_global = 0;
                $repartition_modes = [];
                $factures_seen = [];
                $reste_jour_global = 0;
            @endphp

            @forelse($result->groupBy('facture_id') as $facture_id => $items)

                @php
                    $facture = optional($items->first())->facture;

                if (!$facture) continue;

                $montant_facture = $facture->amount_client ?? 0;

                /**
                 * 🔥 TOUS les mouvements (affichage)
                 */
                $session_elements_all = $facture->sessionElements()
                    ->where('is_deleted', false)
                    ->orderBy('created_at')
                    ->get();

                /**
                 * 🔥 MOUVEMENTS DE LA PÉRIODE
                 */
                $session_elements_filtered = $facture->sessionElements()
                    ->where('is_deleted', false)
                    ->whereBetween('created_at', [
                        \Carbon\Carbon::parse($start)->startOfDay(),
                        \Carbon\Carbon::parse($end)->endOfDay(),
                    ])
                    ->orderBy('created_at')
                    ->get();

                /**
                 * 💰 encaissé sur période
                 */
                $montant_encaisse_facture = $session_elements_filtered->sum('montant');

                /**
                 * 🔥 TOTAL ENCAISSÉ GLOBAL (OK)
                 */
                $total_encaisse_global += $montant_encaisse_facture;

                /**
                 * 🔥 FACTURE comptée UNE SEULE FOIS si mouvement dans période
                 */
                if ($session_elements_filtered->isNotEmpty()) {
                    $factures_seen[$facture->id] = $montant_facture;
                }
                $total_facture_global = array_sum($factures_seen);

                /**
                 * 💡 RESTE DU JOUR (IMPORTANT CORRECTION)
                 * 👉 basé sur les mouvements du jour uniquement
                 */
                $montant_encaisse_total_facture = $session_elements_all->sum('montant');

                $reste_facture = max($montant_facture - $montant_encaisse_total_facture,0);

                $reste_jour_global = ($reste_jour_global ?? 0) + $reste_facture;

                /**
                 * 🔥 RÉPARTITION MODES (période uniquement)
                 */
                foreach ($session_elements_filtered as $item) {
                    $modeName = optional($item->regulation_method)->name ?? 'Indéfini';
                    $repartition_modes[$modeName] = ($repartition_modes[$modeName] ?? 0) + $item->montant;
                }
                @endphp

                {{-- HEADER FACTURE --}}
                <tr style="background-color: #f8f9fa;">
                    <th colspan="9" class="py-2 px-3" style="border-left: 4px solid #333;">
                        <div class="d-flex justify-content-between align-items-center">

                            <strong>FACTURE N°{{ $facture->code ?? '-' }}</strong>

                            <span>
                        Total:
                        <strong>{{ \App\Helpers\FormatPrice::format($montant_facture) }}</strong> |

                        Encaissé:
                        <strong class="text-success">
                            {{ \App\Helpers\FormatPrice::format($montant_encaisse_facture) }}
                        </strong> |

                        Reste:
                        <strong class="{{ $reste_facture > 0 ? 'text-danger' : 'text-dark' }}">
                            {{ \App\Helpers\FormatPrice::format($reste_facture) }}
                        </strong>
                    </span>

                        </div>
                    </th>
                </tr>

                @php
                    $reste = $montant_facture;
                @endphp

                @foreach($session_elements_all as $index => $item)

                    @php
                        $paiement = $item->montant;

                        $reste_avant = $reste;
                        $reste = max($reste - $paiement, 0);

                        $cumul = $montant_facture - $reste;

                        $modeName = optional($item->regulation_method)->name ?? 'Indéfini';
                    @endphp

                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $facture->code ?? '-' }}</td>
                        <td>{{ optional($facture->prestation->client)->nomcomplet_client ?? '-' }}</td>

                        <td>{{ \App\Helpers\FormatPrice::format($montant_facture) }}</td>

                        <td class="font-weight-bold text-success">
                            {{ \App\Helpers\FormatPrice::format($paiement) }}
                        </td>

                        <td>{{ $modeName }}</td>

                        <td>{{ optional($item->regulation)->reference ?? '' }}</td>

                        <td>{{ optional($item->created_at)->format('d/m/Y H:i') }}</td>

                        <td class="text-danger">
                            {{ \App\Helpers\FormatPrice::format($reste) }}
                        </td>
                    </tr>

                @endforeach

            @empty
                <tr>
                    <td colspan="9" class="text-center py-4">Aucune donnée trouvée</td>
                </tr>
            @endforelse

            </tbody>
        </table>

        {{-- TOTAL GLOBAL --}}
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
                <div class="card border-danger shadow-sm">
                    <div class="card-body p-3 text-center">
                        <h6 class="text-danger uppercase small font-weight-bold">Reste à Recouvrer</h6>
                        <h3 class="mb-0 text-danger">{{ \App\Helpers\FormatPrice::format($reste_jour_global) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        {{-- REPARTITION --}}
        @if(count($repartition_modes) > 0)
            <div class="mt-4">
                <h6 class="fw-bold text-uppercase small text-muted mb-3 text-center">
                    Répartition par mode de règlement
                </h6>

                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-3 justify-content-center">
                    @foreach($repartition_modes as $mode => $montant)
                        <div class="col">
                            <div class="card border-success shadow-sm h-100"
                                 style="border-radius: 10px; border-left: 4px solid #28a745 !important;">
                                <div class="card-body py-3 px-2 text-center">

                                    <div class="text-uppercase text-muted mb-2"
                                         style="font-size: 11px; letter-spacing: 0.5px;">
                                        {{ $mode }}
                                    </div>

                                    <div class="fw-bold text-primary fs-5">
                                        {{ \App\Helpers\FormatPrice::format($montant) }}
                                    </div>

                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</div>
</body>
</html>
