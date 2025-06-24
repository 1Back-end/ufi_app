@php use App\Helpers\FormatPrice; @endphp

@extends('pdfs.layouts.template')

@section('content')

<h1 class="fs-3 fw-bold text-center text-uppercase text-decoration-underline">
    LISTE ELEMEMENTS DE FACTURES
</h1>

<p class="fst-italic text-end">Date d'impression: {{ now()->format('d/m/Y H:i') }}</p>

<h2 class="fw-bold text-center fs-5">
    {{ $centre->name }} Période {{ $start_date->format('d/m/Y') }} au {{ $end_date->format('d/m/Y') }}
</h2>

<table class="table table-bordered">
    <thead>
    <tr>
        <th>N° Facture</th>
        <th>Eléments</th>
        <th>Date facture</th>
        <th>PU</th>
        <th>Remise</th>
        <th>PC</th>
    </tr>
    </thead>
    <tbody>
        @if($prestationsPrisCharges->isNotEmpty())
            <tr>
                <td colspan="6" class="border-0 fw-bold">Prise en charge: Oui</td>
            </tr>
            @foreach($prestationsPrisCharges as $prestation)
                @foreach($prestation->actes as $acte)
                    <tr>
                        <td>{{ $prestation->factures[0]->code }}</td>
                        <td>
                            {{ $acte->name }}
                        </td>
                        <td>{{ $prestation->factures[0]->date_fact->format("d/m/Y H:i") }}</td>
                        <td>{{ FormatPrice::format($acte->pivot->b * $acte->pivot->k_modulateur) }}</td>
                        <td>{{ FormatPrice::format($prestation->factures[0]->amount_remise) }}</td>
                        <td>{{ FormatPrice::format($prestation->factures[0]->amount_pc) }}</td>
                    </tr>
                @endforeach

                @foreach($prestation->hospitalisations as $hospitalisation)
                    <tr>
                        <td>{{ $prestation->factures[0]->code }}</td>
                        <td>{{ $hospitalisation->name }}</td>
                        <td>{{ $prestation->factures[0]->date_fact->format("d/m/Y H:i") }}</td>
                        <td>{{ FormatPrice::format($hospitalisation->pivot->pu) }}</td>
                        <td>{{ FormatPrice::format($prestation->factures[0]->amount_remise) }}</td>
                        <td>{{ FormatPrice::format($prestation->factures[0]->amount_pc) }}</td>
                    </tr>
                @endforeach

                @foreach($prestation->consultations as $consultation)
                    <tr>
                        <td>{{ $prestation->factures[0]->code }}</td>
                        <td>{{ $consultation->name }}</td>
                        <td>{{ $prestation->factures[0]->date_fact->format("d/m/Y H:i") }}</td>
                        <td>{{ FormatPrice::format($consultation->pivot->pu) }}</td>
                        <td>{{ FormatPrice::format($prestation->factures[0]->amount_remise) }}</td>
                        <td>{{ FormatPrice::format($prestation->factures[0]->amount_pc) }}</td>
                    </tr>
                @endforeach

                @foreach($prestation->products as $product)
                    <tr>
                        <td>{{ $prestation->factures[0]->code }}</td>
                        <td>{{ $product->name }}</td>
                        <td>{{ $prestation->factures[0]->date_fact->format("d/m/Y H:i") }}</td>
                        <td>{{ FormatPrice::format($product->pivot->pu) }}</td>
                        <td>{{ FormatPrice::format($prestation->factures[0]->amount_remise) }}</td>
                        <td>{{ FormatPrice::format($prestation->factures[0]->amount_pc) }}</td>
                    </tr>
                @endforeach
            @endforeach
            <tr>
                <td class="border-0"></td>
                <td class="border-0"></td>
                <td class="border-0"></td>
                <td class="border-0">{{ FormatPrice::format($amountPrisCharges) }}</td>
                <td class="border-0"></td>
                <td class="border-0"></td>
            </tr>
        @endif

        @if($prestationsNonPrisCharges->isNotEmpty())
            <tr>
                <td colspan="6" class="border-0 fw-bold">Prise en charge: NON</td>
            </tr>
            @foreach($prestationsNonPrisCharges as $prestation)
                @foreach($prestation->actes as $acte)
                    <tr>
                        <td>{{ $prestation->factures[0]->code }}</td>
                        <td>
                            {{ $acte->name }}
                        </td>
                        <td>{{ $prestation->factures[0]->date_fact->format("d/m/Y H:i") }}</td>
                        <td>{{ FormatPrice::format($acte->pivot->b * $acte->pivot->k_modulateur) }}</td>
                        <td>{{ FormatPrice::format($prestation->factures[0]->amount_remise) }}</td>
                        <td>{{ FormatPrice::format($prestation->factures[0]->amount_pc) }}</td>
                    </tr>
                @endforeach

                @foreach($prestation->hospitalisations as $hospitalisation)
                    <tr>
                        <td>{{ $prestation->factures[0]->code }}</td>
                        <td>{{ $hospitalisation->name }}</td>
                        <td>{{ $prestation->factures[0]->date_fact->format("d/m/Y H:i") }}</td>
                        <td>{{ FormatPrice::format($hospitalisation->pivot->pu) }}</td>
                        <td>{{ FormatPrice::format($prestation->factures[0]->amount_remise) }}</td>
                        <td>{{ FormatPrice::format($prestation->factures[0]->amount_pc) }}</td>
                    </tr>
                @endforeach

                @foreach($prestation->consultations as $consultation)
                    <tr>
                        <td>{{ $prestation->factures[0]->code }}</td>
                        <td>{{ $consultation->name }}</td>
                        <td>{{ $prestation->factures[0]->date_fact->format("d/m/Y H:i") }}</td>
                        <td>{{ FormatPrice::format($consultation->pivot->pu) }}</td>
                        <td>{{ FormatPrice::format($prestation->factures[0]->amount_remise) }}</td>
                        <td>{{ FormatPrice::format($prestation->factures[0]->amount_pc) }}</td>
                    </tr>
                @endforeach

                @foreach($prestation->products as $product)
                    <tr>
                        <td>{{ $prestation->factures[0]->code }}</td>
                        <td>{{ $product->name }}</td>
                        <td>{{ $prestation->factures[0]->date_fact->format("d/m/Y H:i") }}</td>
                        <td>{{ FormatPrice::format($product->pivot->pu) }}</td>
                        <td>{{ FormatPrice::format($prestation->factures[0]->amount_remise) }}</td>
                        <td>{{ FormatPrice::format($prestation->factures[0]->amount_pc) }}</td>
                    </tr>
                @endforeach
            @endforeach
            <tr>
                <td class="border-0"></td>
                <td class="border-0"></td>
                <td class="border-0"></td>
                <td class="border-0">{{ FormatPrice::format($amountNonPrisCharges) }}</td>
                <td class="border-0"></td>
                <td class="border-0"></td>
            </tr>
        @endif
    </tbody>
</table>

<p class="d-flex justify-content-end fw-bold fs-5 gap-5">
    <span class="text-decoration-underline">Grand Total:</span>
    <span
        class="text-decoration-underline">{{  FormatPrice::format($amountPrisCharges + $amountNonPrisCharges) }}</span>
</p>

@endsection
