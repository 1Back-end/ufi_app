@extends('pdfs.layouts.template')

@section('content')
    <div class="row">
        <div class="col-4"></div>
        <div class="text-dark mt-2 text-center col">
            <p>{{ $centre->town }}, le {{ now()->translatedFormat('j F Y') }}</p>

            <div class="mt-2" style="font-weight: bold; line-height: 1.4;">
                <p class="my-0">A</p>
                <p class="my-0">L'attention de Monsieur le Directeur Général</p>

                @if($assurance?->nom)
                    <p class="my-0">{{ $assurance->nom }}</p>
                @endif

                @if($assurance?->bp)
                    <p class="my-0">BP: {{ $assurance->bp }}</p>
                @endif

                @if($assurance?->tel)
                    <p class="my-0">Tél: {{ $assurance->tel }}</p>
                @endif
            </div>
        </div>
    </div>

    <p class="text-center w-100 text-decoration-underline">
        FACTURE N° {{ $code }}
    </p>

    <div class="">
        <span class="text-decoration-underline">Objet:</span>
        <span>{{ $factureAssurance->object_of_facture_assurance ?? 'Objet non défini' }}</span>

        <p>Période: du {{ $start_date->translatedFormat('j F Y') }} au {{ $end_date->translatedFormat('j F Y') }}</p>
    </div>

    @php
        $i = 1;
    @endphp
    <div class="d-flex justify-content-center mt-2">
        @php
            $totalReclame = 0;
            $totalModerateur = 0;
        @endphp

        <table class="table border-dark table-bordered">
            <thead>
            <tr>
                <th style="width: 30px;">N°</th>
                <th style="width: 50px;">Date</th>
                <th>Nom du patient</th>
                <th>Code facture</th>
                <th>Montant Réclamé</th>
                <th>Modérateur</th>
            </tr>
            </thead>
            <tbody>
            @foreach($prestations as $prestation)
                @foreach($prestation->factures as $facture)
                    @php
                        // On accumule les montants à chaque passage
                        $totalReclame += $facture->amount;
                        $totalModerateur += $facture->amount_client;
                    @endphp
                    <tr>
                        <td>{{ $i++ }}</td>
                        <td>{{ $facture->date_fact->format('d/m/Y') }}</td>
                        <td>{{ $prestation->client->nomcomplet_client }}</td>
                        <td>{{ $facture->code }}</td>
                        <td>{{ \App\Helpers\FormatPrice::format($facture->amount) }}</td>
                        <td>{{ \App\Helpers\FormatPrice::format($facture->amount_client) }}</td>
                    </tr>
                @endforeach
            @endforeach
            </tbody>
            <tfoot>
            <tr class="fw-bold bg-light">
                <td colspan="4" class="text-end">Montant net ttc</td>
                <td>
                    {{ \App\Helpers\FormatPrice::format($totalReclame) }}
                </td>
                <td>
                    {{ \App\Helpers\FormatPrice::format($totalModerateur) }}
                </td>
            </tr>
            </tfoot>
        </table>
    </div>

    @php
        $montantNet = $totalReclame - $totalModerateur;
        $f = new NumberFormatter("fr", NumberFormatter::SPELLOUT);
        $montantEnLettres = ucfirst($f->format($montantNet));
        $montantEnLettres = str_replace('-', ' ', $montantEnLettres);
    @endphp

    <p>{{ $montantEnLettres }} francs CFA</p>

    <div class="mt-4">
        <p style="font-size: 14px;">
            Arrête la présente facture à la somme de :
            <strong>{{ $montantEnLettres }} francs CFA</strong>
        </p>
    </div>

    <div class="mt-4" style="line-height: 1.6;">
        @if($factureAssurance?->mode_of_payment)
            <p class="mb-1">
                Mode de paiement : {{ $factureAssurance->mode_of_payment }}
            </p>
        @endif

        @if($factureAssurance?->compte_or_payment)
            <p class="mb-1">
                compte {{ $factureAssurance->compte_or_payment }}
            </p>
        @endif

        @if($factureAssurance?->number_for_compte)
            <p class="mb-1" style="font-size: 14px;">
                <strong>N°{{ $factureAssurance->number_for_compte }}</strong>
            </p>
        @endif

        <p class="mt-3 font-italic">
            {{ $factureAssurance->text_of_remerciement ?? 'Merci de votre confiance.' }}
        </p>
    </div>





@endsection
