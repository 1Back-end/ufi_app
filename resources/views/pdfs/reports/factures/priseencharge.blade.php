@extends('pdfs.layouts.template')

@section('content')

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
        @if(!in_array($priseCharge->assureur->id, $assurances) && $priseCharge->prestations->count())
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
                N° Facture: <span class="fw-bold text-uppercase">{{  $prestation->factures[0]->code}}</span>
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
                            <td>{{ intval($acte->pivot->b) }}</td>
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
                            <td>{{ intval($hospitalisation->pivot->b) }}</td>
                            <td>{{ $priseCharge->taux_pc . '%' }}</td>
                            <td>{{ \App\Helpers\FormatPrice::format(($hospitalisation->pivot->pu * $hospitalisation->pivot->remise) / 100) }}
                            </td>
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
                            <td>{{ intval($consultation->pivot->b) }}</td>
                            <td>{{ $priseCharge->taux_pc . '%' }}</td>
                            <td>{{ \App\Helpers\FormatPrice::format(($consultation->pivot->pu * $consultation->pivot->remise) / 100) }}
                            </td>
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
                            <td>{{ intval($consultation->pivot->b) }}</td>
                            <td>{{ $priseCharge->taux_pc . '%' }}</td>
                            <td>{{ \App\Helpers\FormatPrice::format(($consultation->pivot->pu * $consultation->pivot->remise) / 100) }}
                            </td>
                            <td>{{ \App\Helpers\FormatPrice::format(($consultation->pivot->pu * $priseCharge->taux_pc) / 100) }}</td>
                        </tr>
                    @endforeach

                    @foreach($prestation->examens as $examen)
                        <tr>
                            <td>{{ $prestation->factures[0]->code }}</td>
                            <td>{{ $prestation->factures[0]->date_fact->format("d/m/Y H:i") }}</td>
                            <td>
                                {{ $examen->name }}
                            </td>
                            <td>{{ \App\Helpers\FormatPrice::format($examen->pivot->pu) }}</td>
                            <td>{{ intval($examen->pivot->b) }}</td>
                            <td>{{ $priseCharge->taux_pc . '%' }}</td>
                            <td>{{ \App\Helpers\FormatPrice::format(($examen->pivot->pu * $examen->pivot->remise) / 100) }}</td>
                            <td>{{ \App\Helpers\FormatPrice::format(($examen->pivot->pu * $priseCharge->taux_pc) / 100) }}</td>
                        </tr>
                    @endforeach

                    @foreach($prestation->soins as $soin)
                        <tr>
                            <td>{{ $prestation->factures[0]->code }}</td>
                            <td>{{ $prestation->factures[0]->date_fact->format("d/m/Y H:i") }}</td>
                            <td>
                                {{ $soin->name }}
                            </td>
                            <td>{{ \App\Helpers\FormatPrice::format($soin->pivot->pu) }}</td>
                            <td>{{ intval($soin->pivot->b )}}</td>
                            <td>{{ $priseCharge->taux_pc . '%' }}</td>
                            <td>{{ \App\Helpers\FormatPrice::format(($soin->pivot->pu * $soin->pivot->remise) / 100) }}</td>
                            <td>{{ \App\Helpers\FormatPrice::format(($soin->pivot->pu * $priseCharge->taux_pc) / 100) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-0">
                        <td colspan="3" class="border-0 text-end">Totaux</td>
                        <td colspan="" class="border-0">
                            <span class="">Total:</span>
                            <span
                                class="text-decoration-underline fw-bold">{{  \App\Helpers\FormatPrice::format($prestation->factures[0]->amount) }}</span>
                        </td>
                        <td class="border-0">
                            <span>Montant PC:</span>
                            <span
                                class="text-decoration-underline fw-bold">{{  \App\Helpers\FormatPrice::format($prestation->factures[0]->amount_pc) }}</span>
                        </td>
                        <td class="border-0">
                            <span>Montant réglé:</span>
                            <span
                                class="text-decoration-underline fw-bold">{{  \App\Helpers\FormatPrice::format($prestation->factures[0]->regulations_total_except_particular) }}</span>
                        </td>
                        <td class="border-0">
                            <span>Remise:</span>
                            <span
                                class="text-decoration-underline fw-bold">{{  \App\Helpers\FormatPrice::format($prestation->factures[0]->amount_remise) }}</span>
                        </td>
                        <td class="border-0">
                            <span>Reste à payer:</span>
                            <span
                                class="text-decoration-underline fw-bold">{{  \App\Helpers\FormatPrice::format($prestation->factures[0]->amount_rest) }}</span>
                        </td>
                    </tr>
                </tfoot>
            </table>

            @if(!($loop->last && $loop->parent->last))
                <hr class="my-2">
            @endif
        @endforeach
    @endforeach

    <div class="row row-cols-4">
        <div class="d-flex gap-2 align-items-center mb-3">
            <span class="">Total:</span>
            <span
                class="text-decoration-underline fw-bold">{{  \App\Helpers\FormatPrice::format($amounts['total']) }}</span>
        </div>

        <div class="d-flex gap-2 align-items-center mb-3">
            <span class="">Total prise en charge:</span>
            <span
                class="text-decoration-underline fw-bold">{{  \App\Helpers\FormatPrice::format($amounts['total_pc']) }}</span>
        </div>

        <div class="d-flex gap-2 align-items-center mb-3">
            <span class="">Reste à payer:</span>
            <span
                class="text-decoration-underline fw-bold">{{  \App\Helpers\FormatPrice::format($amounts['amount_rest']) }}</span>
        </div>

        <div class="d-flex gap-2 align-items-center mb-3">
            <span class="">Total Réglement:</span>
            <span
                class="text-decoration-underline fw-bold">{{  \App\Helpers\FormatPrice::format($amounts['amount_total_regulation']) }}</span>
        </div>

        <div class="d-flex gap-2 align-items-center mb-3">
            <span class="">Total Remise:</span>
            <span
                class="text-decoration-underline fw-bold">{{  \App\Helpers\FormatPrice::format($amounts['total_remise']) }}</span>
        </div>
    </div>

@endsection
