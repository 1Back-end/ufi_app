<!doctype html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

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

    <title>{{ $filename }}</title>
</head>

<body>
{{-- Header --}}
<header class="d-flex align-items-center size">
    <div class="w-25">
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path($logo))) }}" alt="" class="img-fluid w-75">
    </div>

    <div class="text-center">
        <div class="fs-1 text-uppercase">
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
            NUI: {{ $centre->contribuable }}
        </div>
    </div>
</header>

<div class="mt-1 w-100" style="border-top: 1px double rgb(0, 0, 0, 0.75); margin-bottom: 0.5px"></div>
<div class="mb-1 w-100" style="border-top: 1px double rgb(0, 0, 0, 0.75);"></div>

<div class="">
    <div class="d-flex justify-content-between mt-1">
        <div class="d-flex gap-2 align-items-center">
            <div class="fst-italic">Date d'impression:</div>
            <div class="">
                {{ $print_date }}
            </div>
        </div>

        <div class="d-flex gap-2 align-items-center">
            <div class="fst-italic">Date de saisie:</div>
            <div class="">
                {{ $date_saisie }}
            </div>
        </div>

        <div class="d-flex gap-2 align-items-center">
            <div class="fst-italic">Résultat complet:</div>
            <div class="">
                {{ $date_saisie }}
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <div class="d-flex align-items-center gap-2">
            <div class="fst-italic">N° Facture:</div>
            <div class="fw-bold">{{ $facture ? $facture->code : '' }}</div>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <div class="">
            <table>
                <tbody>
                <tr>
                    <td class="">Code client:</td>
                    <td class="ps-3 fw-bold">{{ $prestation->client->ref_cli }}</td>
                </tr>

                <tr>
                    <td class="">Nom du patient:</td>
                    <td class="ps-3 fw-bold">{{ $prestation->client->nomcomplet_client }}</td>
                </tr>

                <tr>
                    <td class="">Age du patient:</td>
                    <td class="ps-3 fw-bold">{{ $prestation->client->age }} ans</td>
                </tr>

                <tr>
                    <td class="">Prescripteur:</td>
                    <td class="ps-3 fw-bold">{{ $prestation->consultant?->nomcomplet }}</td>
                </tr>

                <tr>
                    <td class="">Date de prélèvement:</td>
                    <td class="ps-3 fw-bold">{{ $preleve_date }}</td>
                </tr>

                <tr>
                    <td class="">Renseignements cliniques:</td>
                    <td class="ps-3 fw-bold"></td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="" style="width: 30%">
            <div class="bg-white text-center border border-black border-bottom-0">Visa du Médecin</div>
            <div class="border border-black p-5"></div>
        </div>
    </div>

    <div class="d-flex justify-content-between fw-bold size">
        <span>&nbsp;</span>
        <span style="font-size: 10px">
            {{ in_array($prestation->state_examen, [8, 9, 10]) ? 'Compte rendu partiel' : 'Compte rendu complet' }}
        </span>
    </div>

    <div class="border border-black" style="border-color: rgba(0, 0, 0, 0.75);">
        <table class="table">
            <thead>
            <tr>
                <th style="background-color: #ccc; padding: 2px;" class="" scope="col">Analyse</th>
                <th style="background-color: #ccc; padding: 2px;" class="" scope="col">Résultat</th>
                <th style="background-color: #ccc; padding: 2px;" class="" scope="col">Antériorités</th>
                <th style="background-color: #ccc; padding: 2px;" class="" scope="col">Valeurs normales</th>
            </tr>
            </thead>

            <tbody>
            @foreach($prestation->examens as $index => $examen)
                @if(showExamHasResult($prestation, $examen))
                    @if(showResultExamen($prestation, $examen))
                        <tr>
                            <td class="border-start-0 border-end-0 border-top-0" style="border-style: dotted; padding: 2px">
                                <strong>{{ $examen->name }}</strong>
                            </td>

                            <td class="border-start-0 border-end-0 border-top-0" style="border-style: dotted; padding: 2px">
                                <p class="fw-bold" style="margin: 0;">
                                    {{ showResultExamen($prestation, $examen)->result_client }} {{ showResultExamen($prestation, $examen)->elementPaillasse->unit }}
                                </p>
                            </td>

                            <td class="border-start-0 border-end-0 border-top-0" style="border-style: dotted; padding: 2px">
                                @foreach($anteriorities as $anteriority)
                                    @if($anteriority['element_paillasse_id'] == showResultExamen($prestation, $examen)->elementPaillasse->id)
                                        <div class="fst-italic" style="font-size: 0.8rem">
                                            {{ $anteriority->result->result_client }} {{ showResultExamen($prestation, $examen)->elementPaillasse->unit }} ({{ $anteriority->result->created_at->format('d/m/Y') }})
                                        </div>
                                    @endif
                                @endforeach
                            </td>

                            <td class="border-start-0 border-end-0 border-top-0" style="border-style: dotted; padding: 2px">
                                @foreach(showResultExamen($prestation, $examen)->elementPaillasse->group_populations as $population)
                                    @if($population->sex_id == $prestation->client->sexe_id && ($population->agemin <= $prestation->client->age * 12 && $population->agemax >= $prestation->client->age * 12))
                                        @if($population->pivot->sign == '[]')
                                            {{ $population->pivot->value }} - {{ $population->pivot->value_max }} {{ showResultExamen($prestation, $examen)->elementPaillasse->unit }}
                                        @else
                                            {{ $population->pivot->sign }} {{ $population->pivot->value }} {{ showResultExamen($prestation, $examen)->elementPaillasse->unit }}
                                        @endif
                                    @endif
                                @endforeach
                            </td>
                        </tr>
                    @else
                        <tr>
                            <td colspan="5" class="border-start-0 border-end-0 border-top-0" style="border-style: dotted; padding: 2px">
                                <strong>{{ $examen->name }}</strong>
                            </td>
                        </tr>
                    @endif

                    @foreach($examen->elementPaillasses->sortBy('num') as $index => $elementPaillasse)
                        @if(showResult($prestation, $elementPaillasse, $examen) && $elementPaillasse->typeResult->afficher_result)
                            @if($elementPaillasse->typeResult->type == 'inline')
                                <tr>
                                    <td colspan="5" class="" style="padding: 2px"></td>
                                </tr>
                            @endif

                            @if($elementPaillasse->typeResult->type == 'group')
                                <tr>
                                    <td colspan="5" class="" style="padding: 2px">
                                        @if($elementPaillasse->hide_label)
                                            <p class="fw-bold text-primary fs-5 text-center" style="margin: 0;">
                                                {{ $elementPaillasse->name }}
                                            </p>
                                        @endif
                                    </td>
                                </tr>
                            @endif

                            @if($elementPaillasse->typeResult->type == 'comment')
                                <tr>
                                    <td colspan="5" class="" style="padding: 2px">
                                        <div class="w-50">
                                            @foreach($prestation->results as $result)
                                                @if($result->element_paillasse_id == $elementPaillasse->id && $result->prestation_id == $prestation->id)
                                                    <p class="fw-bold text-primary fs-5 text-center" style="margin: 0;">
                                                        {{ $result->result_machine }}
                                                    </p>
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endif

                            @if (!($elementPaillasse->typeResult->type == 'group' || $elementPaillasse->typeResult->type == 'inline' || $elementPaillasse->typeResult->type == 'comment'))
                                <tr>
                                    <td class="border-0" style="padding-left: {{ ($elementPaillasse->indent == 0 ? 1 : $elementPaillasse->indent * 1.2) * 1.1 }}rem">
                                        @if(!$elementPaillasse->hide_label)
                                            <p style="margin: 0;">{{ $elementPaillasse->name }}</p>
                                        @endif
                                    </td>

                                    @foreach($prestation->results as $result)
                                        @if($result->element_paillasse_id == $elementPaillasse->id && $result->prestation_id == $prestation->id)
                                            <td class="border-0" style="padding: 2px">
                                                <span class="fw-medium">{{ $result->result_client }} {{ $elementPaillasse->unit }}</span>
                                            </td>

                                            <td class="border-0" style="padding: 2px">
                                                @foreach($anteriorities as $anteriority)
                                                    @if($anteriority['element_paillasse_id'] == $elementPaillasse->id)
                                                        <div class="fst-italic" style="font-size: 0.8rem">
                                                            {{ $anteriority->result->result_client }} {{ $elementPaillasse->unit }} ({{ $anteriority->result->created_at->format('d/m/Y') }})
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </td>
                                        @endif
                                    @endforeach

                                    <td class="border-0" style="padding: 2px">
                                        @foreach($elementPaillasse->group_populations as $population)
                                            @if($population->sex_id == $prestation->client->sexe_id && ($population->agemin <= $prestation->client->age * 12 && $population->agemax >= $prestation->client->age * 12))
                                                @if($population->pivot->sign == '[]')
                                                    {{ $population->pivot->value }} - {{ $population->pivot->value_max }} {{ $elementPaillasse->unit }}
                                                @else
                                                    {{ $population->pivot->sign }} {{ $population->pivot->value }} {{ $elementPaillasse->unit }}
                                                @endif
                                            @endif
                                        @endforeach
                                    </td>

                                </tr>
                            @endif
                        @endif
                    @endforeach
                @endif
            @endforeach
            </tbody>
        </table>
    </div>

</div>

</body>

</html>
