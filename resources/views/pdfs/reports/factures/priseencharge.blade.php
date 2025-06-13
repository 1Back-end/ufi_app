<!doctype html>
<html lang="fr">

<head>
    <meta charset="UTF-8">

    <style>
        {!! $bootstrap !!}
    </style>

    <style>
        body,
        html {
            height: 100%;
            margin: 0;
            padding: 0;
            font-size: 3mm !important;
            font-family: "Times New Roman", serif;
        }

        header {
            font-family: 'Helvetica', serif;
        }

        h1 {
            font-size: 6mm !important;
        }

        table {
            font-size: 3mm !important;
        }

        img {
            width: auto;
            height: auto;
        }

        .print-wrapper {
            position: relative;
            min-height: 100%;
            padding-bottom: 10mm;
            box-sizing: border-box;
        }

        .print-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 10mm;
            text-align: center;
            background-color: white;
            border-top: 1mm solid rgb(15, 187, 105);
            opacity: .5;
            font-size: 2.5mm;
        }
    </style>
</head>

<body>
{{-- Header --}}
<header class="row pb-2 border-bottom border-1 border-black">
    <div class="col-4">
        @if($logo)
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path($logo))) }}" alt="">
        @endif
    </div>

    <div class="text-center col text-success">
        <div class="fs-1 text-uppercase">
            {{ $centre->name }}
        </div>

        <hr class="my-1 border border-success border-1 opacity-75 col-12">

        <div class="">
            - {{ $centre->address }} - {{ $centre->town }}
        </div>

        <div class="">
            BP: {{ $centre->postal_code }} {{ $centre->town }} -
            Tél. {{ $centre->tel }} {{ $centre->tel2 ? '/' . $centre->tel2 : '' }}
            / Fax: {{ $centre->fax }}
        </div>

        <div class="">
            Email: {{ $centre->email }}
        </div>

        <div class="">
            Autorisation n° {{ $centre->autorisation }}
            NUI: {{ $centre->contribuable }}
        </div>
    </div>
</header>

<h1 class="fs-3 fw-bold text-center text-uppercase text-decoration-underline">
    RELEVE DES PRISES EN CHARGE
</h1>

<p class="fst-italic text-end">Date d'impression: {{ now()->format('d/m/Y H:i') }}</p>

<h2 class="fw-bold text-center fs-5">
    {{ $centre->name }} Période {{ $start_date->format('d/m/Y') }} au {{ $end_date->format('d/m/Y') }}
</h2>

@php
    $assurances = [];
@endphp

@foreach($priseCharges as $priseCharge)
    @if(! in_array($priseCharge->assureur->id, $assurances))
        <p class="d-flex flex-column fs-5">
            <span class=" ">
                Assureur: <span class="text-uppercase fw-bold">{{ $priseCharge->assureur->nom }}</span>
            </span>

            <span>
                Adresse: <span class="text-uppercase">{{ $priseCharge->assureur->adresse }}</span>
            </span>
        </p>

        @php
            $assurances[] = $priseCharge->assureur->id;
        @endphp
    @endif

    @foreach($priseCharge->prestations as $prestation)
        <p>
            N° Facture:  <span class="fw-bold text-uppercase">{{  $prestation->factures[0]->code}}</span>
        </p>

        <p class="">
            Patient: <span class="fw-bold text-uppercase">{{ $priseCharge->client->nomcomplet_client }}</span>
        </p>

        <table class="table table-bordered">
            <thead>
            <tr>
                <th>N° Facture</th>
                <th>Date facture</th>
                <th>{{ $prestation->type_label }}</th>
                <th>Prix U</th>
                <th>B</th>
                <th>Taux PC</th>
                <th>Remise</th>
                <th>Montant PC</th>
            </tr>
            </thead>
            <tbody>
                @foreach($prestation->actes as $acte)
                    <tr>
                        <td>{{ $prestation->factures[0]->code }}</td>
                        <td>{{ $prestation->factures[0]->date_fact->format("d/m/Y H:i") }}</td>
                        <td>
                            {{ $acte->name }}
                        </td>
                        <td>{{ \App\Helpers\FormatPrice::format($acte->pivot->pu) }}</td>
                        <td>{{ $acte->pivot->b }}</td>
                        <td>{{ $priseCharge->taux_pc . '%' }}</td>
                        <td>{{ \App\Helpers\FormatPrice::format(($acte->pivot->pu * $acte->pivot->remise) / 100) }}</td>
                        <td>{{ \App\Helpers\FormatPrice::format(($acte->pivot->pu * $priseCharge->taux_pc) / 100) }}</td>
                    </tr>
                @endforeach

                @foreach($prestation->hospitalisations as $hospitalisation)
                    <tr>
                        <td>{{ $prestation->factures[0]->code }}</td>
                        <td>{{ $prestation->factures[0]->date_fact->format("d/m/Y H:i") }}</td>
                        <td>
                            {{ $hospitalisation->name }}
                        </td>
                        <td>{{ \App\Helpers\FormatPrice::format($hospitalisation->pivot->pu) }}</td>
                        <td>{{ $hospitalisation->pivot->b }}</td>
                        <td>{{ $priseCharge->taux_pc . '%' }}</td>
                        <td>{{ \App\Helpers\FormatPrice::format(($hospitalisation->pivot->pu * $hospitalisation->pivot->remise) / 100) }}</td>
                        <td>{{ \App\Helpers\FormatPrice::format(($hospitalisation->pivot->pu * $priseCharge->taux_pc) / 100) }}</td>
                    </tr>
                @endforeach

                @foreach($prestation->consultations as $consultation)
                    <tr>
                        <td>{{ $prestation->factures[0]->code }}</td>
                        <td>{{ $prestation->factures[0]->date_fact->format("d/m/Y H:i") }}</td>
                        <td>
                            {{ $consultation->name }}
                        </td>
                        <td>{{ \App\Helpers\FormatPrice::format($consultation->pivot->pu) }}</td>
                        <td>{{ $consultation->pivot->b }}</td>
                        <td>{{ $priseCharge->taux_pc . '%' }}</td>
                        <td>{{ \App\Helpers\FormatPrice::format(($consultation->pivot->pu * $consultation->pivot->remise) / 100) }}</td>
                        <td>{{ \App\Helpers\FormatPrice::format(($consultation->pivot->pu * $priseCharge->taux_pc) / 100) }}</td>
                    </tr>
                @endforeach

                @foreach($prestation->products as $product)
                    <tr>
                        <td>{{ $prestation->factures[0]->code }}</td>
                        <td>{{ $prestation->factures[0]->date_fact->format("d/m/Y H:i") }}</td>
                        <td>
                            {{ $consultation->name }}
                        </td>
                        <td>{{ \App\Helpers\FormatPrice::format($consultation->pivot->pu) }}</td>
                        <td>{{ $consultation->pivot->b }}</td>
                        <td>{{ $priseCharge->taux_pc . '%' }}</td>
                        <td>{{ \App\Helpers\FormatPrice::format(($consultation->pivot->pu * $consultation->pivot->remise) / 100) }}</td>
                        <td>{{ \App\Helpers\FormatPrice::format(($consultation->pivot->pu * $priseCharge->taux_pc) / 100) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="fw-bold border-0">
                    <td colspan="7" class="text-end border-0"></td>
                    <td class="border-0">{{ \App\Helpers\FormatPrice::format($prestation->factures[0]->amount_pc) }}</td>
                </tr>
            </tfoot>
        </table>

        @if(! ($loop->last && $loop->parent->last))
            <hr class="my-2">
        @endif
    @endforeach
@endforeach

<p class="d-flex justify-content-between fw-bold fs-5">
    <span class="text-decoration-underline">Grand Total:</span>
    <span class="text-decoration-underline">{{  \App\Helpers\FormatPrice::format($priseCharges->first()?->total_amount) }}</span>
</p>

</body>

</html>
