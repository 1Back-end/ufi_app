@php use Carbon\Carbon; @endphp
    <!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>ETATS DES CONSULTANTS DANS LES RENDEZ-VOUS</title>

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
            @if(!empty($logo))
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path($logo))) }}" alt="Logo" class="img-fluid w-50">
            @endif
        </div>

        <div class="text-center" style="line-height: 18px">
            <div class="fs-3 text-uppercase fw-bold">
                {{ $centre->name ?? '' }}
            </div>

            <div>
                {{ $centre->address ?? '' }} - {{ $centre->town ?? '' }}
            </div>

            <div>
                BP: {{ $centre->postal_code ?? '' }} {{ $centre->town ?? '' }} -
                Tél. {{ $centre->tel ?? '' }} {{ $centre->tel2 ? '/' . $centre->tel2 : '' }}
                / Fax: {{ $centre->fax ?? '' }}
            </div>

            <div>
                Email: {{ $centre->email ?? '' }}
            </div>

            <div>
                Autorisation n° {{ $centre->autorisation ?? '' }} |
                NIU: {{ $centre->contribuable ?? '' }}
            </div>
        </div>
    </header>

    <div class="mt-2 w-100" style="border-top: 1px double rgb(0, 0, 0, 0.75); margin-bottom: 2px"></div>
    <div class="mb-2 w-100" style="border-top: 1px double rgb(0, 0, 0, 0.75);"></div>

    <h1 class="fs-4 fw-bold text-center text-uppercase">
        @if(isset($consultant) && $consultant)
            {{ $consultant->nomcomplet ?? '' }}
        @elseif(isset($client) && $client)
            Client : {{ $client->nomcomplet_client ?? $client->nomcomplet ?? '' }}
        @else
            Liste des rendez-vous
        @endif

        @if(isset($start) && isset($end))
            <span class="fw-normal"> — Du {{ \Carbon\Carbon::parse($start)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($end)->format('d/m/Y') }}</span>
        @endif
    </h1>

    <p class="fst-italic text-end">Date d'impression : {{ now()->format('d/m/Y H:i') }}</p>

    <div class="mt-2 w-100">
        <table class="table table-bordered table-striped" style="font-size: 10px;">
            <thead>
            <tr>
                <th>#</th>
                <th>ID prestation</th>
                <th>Type prestation</th>
                <th>Client</th>
                <th>Consultant</th>
                <th>Montant Prestation</th>
                <th>Honoraires Consultant</th>
                <th>Date de programmation</th>
                <th>Statut</th>
                <th>Date création</th>
            </tr>
            </thead>
            <tbody>
            @php
                $sumMontantPrestation = 0;
                $sumHonorairesConsultant = 0;
            @endphp

            @forelse($result as $index => $item)
                @php
                    $montantPrestation = $item->prestation->factures->sum('amount');
                    $honoraires = $item->prestation->consultant_amount ?? 0;

                    $sumMontantPrestation += $montantPrestation;
                    $sumHonorairesConsultant += $honoraires;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->prestation->id ?? '' }}</td>
                    <td>{{ $item->prestation->type_label ?? '' }}</td>
                    <td>{{ $item->client->nomcomplet_client ?? $item->client->nomcomplet ?? '' }}</td>
                    <td>{{ $item->consultant->nomcomplet ?? '' }}</td>
                    <td>{{ \App\Helpers\FormatPrice::format($montantPrestation) }}</td>
                    <td>
                        {{ \App\Helpers\FormatPrice::format($honoraires) }}

                        @if($honoraires > 0)
                            <br>
                            @php
                                $status = strtolower($item->prestation->consultant_amount_status ?? '');
                                $isPaid = ($status === 'paid');
                            @endphp

                            <span class="{{ $isPaid ? 'text-success' : 'text-warning' }}" style="font-size: 11px; font-weight: 500;">
                                {{ $isPaid ? 'Payé' : 'En attente' }}
                            </span>
                        @endif
                    </td>
                    <td>{{ $item->dateheure_rdv ? \Carbon\Carbon::parse($item->dateheure_rdv)->format('d/m/Y H:i') : '' }}</td>
                    <td>{{ $item->type_label ?? '' }}</td>
                    <td>{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i') : '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center text-muted">Aucune donnée trouvée pour cette période.</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        {{-- Tableau simple de total général par colonne --}}
        @if(isset($consultant) && $consultant && $result->isNotEmpty())
            <table class="table table-bordered fw-bold mt-2" style="font-size: 10px;">
                <tr>
                    <td colspan="5" class="text-end text-uppercase" style="width: 50%;">RECAPITULATIF :</td>
                    <td style="width: 15%;">{{ \App\Helpers\FormatPrice::format($sumMontantPrestation) }}</td>
                    <td style="width: 15%;">{{ \App\Helpers\FormatPrice::format($sumHonorairesConsultant) }}</td>
                    <td colspan="3" style="width: 20%;">Reste : {{ \App\Helpers\FormatPrice::format($sumMontantPrestation - $sumHonorairesConsultant) }}</td>
                </tr>
            </table>


            @if(isset($recap))
                <div class="mt-4 p-3 border" style="font-size: 11px; page-break-inside: avoid;">
                    <h5 class="fw-bold text-uppercase  mb-2" style="font-size: 12px; border-bottom: 1px solid #ccc; padding-bottom: 4px;">
                        État Financier Détaillé du Dr. {{ $consultant->nomcomplet }}
                    </h5>
                    <table class="table table-sm table-bordered text-center mb-0 bg-white" style="font-size: 10px;">
                        <thead class="table-secondary">
                        <tr>
                            <th>Total prestations</th>
                            <th>Montant total prestations</th>
                            <th>Total honoraires consultant</th>
                            <th>Prestations payées</th>
                            <th>Montant payé</th>
                            <th>Prestations en attente</th>
                            <th>Montant en attente</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td class="fw-bold">{{ $recap['total_count'] }}</td>
                            <td class="fw-bold">{{ \App\Helpers\FormatPrice::format($recap['total_prestation_amount'] ?? $sumMontantPrestation) }}</td>
                            <td class="fw-bold text-primary">{{ \App\Helpers\FormatPrice::format($recap['total_amount']) }}</td>
                            <td>{{ $recap['count_paid'] }}</td>
                            <td class="text-success fw-bold">{{ \App\Helpers\FormatPrice::format($recap['amount_paid']) }}</td>
                            <td>{{ $recap['count_pending'] }}</td>
                            <td class="text-warning fw-bold">{{ \App\Helpers\FormatPrice::format($recap['amount_pending']) }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </div>
</div>

</body>
</html>
