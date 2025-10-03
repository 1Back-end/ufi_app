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

        table tbody {
            font-size: 2.5mm !important;
        }

        img {
            width: auto;
            height: auto;
        }
    </style>

    <title>{{ $filename }}</title>
</head>

<body>
{{-- Header --}}
<header class="d-flex align-items-center size" style="font-family: 'Times New Roman', serif">
    <div class="w-25">
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path($logo))) }}" alt="" class="img-fluid w-50">
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
            NUI: {{ $centre->contribuable }}
        </div>
    </div>
</header>

<div class="mt-1 w-100" style="border-top: 1px double rgb(0, 0, 0, 0.75); margin-bottom: 2px"></div>
<div class="mb-1 w-100" style="border-top: 1px double rgb(0, 0, 0, 0.75);"></div>

<div class="">
    <div class="d-flex justify-content-between">
        <div class="" style="font-family: Arial, serif">
            <table>
                <tbody>
                <tr>
                    <td class="">Dossier patient:</td>
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

        <div class="" style="width: 35%">
            <div class="d-flex gap-2 align-items-center text-start">
                <div class="fst-italic">Date d'impression:</div>
                <div class="">
                    {{ $print_date }}
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 text-start">
                <div class="fst-italic">N° Facture:</div>
                <div class="">{{ $facture ? $facture->code : '' }}</div>
            </div>

            <div class="" style="">
                <div class="bg-white text-center border border-black border-bottom-0">Visa du Médecin</div>
                <div class="border border-black" style="padding: 3.5rem; height: 80%"></div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between fw-bold size">
        <span>&nbsp;</span>
        <span style="font-size: 10px">
            {{ in_array($prestation->state_examen, [8, 9, 10]) ? 'Compte rendu partiel' : 'Compte rendu complet' }}
        </span>
    </div>

    <table class="table border border-black" style="border-color: rgb(0, 0, 0, 0.3)">
        <thead>
            <tr class="">
                <th style="background-color: #ccc; padding: 2px;" class="text-center  " scope="col">Analyse</th>
                <th style="background-color: #ccc; padding: 2px;" class=" " scope="col">Résultat</th>
                <th style="background-color: #ccc; padding: 2px;" class="text-center " scope="col">Antériorités</th>
                <th style="background-color: #ccc; padding: 2px;" class="text-center " scope="col">Valeurs normales</th>
            </tr>
        </thead>

        <tbody>
            @foreach($prestation->examens->groupBy(fn ($examen) => $examen->paillasse->name)->sortBy(fn ($value, $key) => $key) as $paillasse => $examens)
                @if(showPaillasseHasResult($prestation, $examens))
                    <tr>
                        <td colspan="5" class="border-start-0 border-top-0 text-uppercase fs-6 text-danger" style="border-style: dotted; font-family: 'Times New Roman', serif">
                            {{ $paillasse }}
                        </td>
                    </tr>
                @endif

                @foreach($examens as $index => $examen)
                    @if(showExamHasResult($prestation, $examen))
                        @if(showResultExamen($prestation, $examen))
                            <tr style="font-family: Arial, serif;">
                                <td class="border-start-0 border-end-0 border-top-0 fw-bold" style="border-style: dotted;">
                                    {{ $examen->name }}
                                </td>

                                <td class="border-start-0 border-end-0 border-top-0" style="border-style: dotted;">
                                    <p class="fw-bold" style="margin: 0;">
                                        {{ showResultExamen($prestation, $examen)->result_client }} {{ showResultExamen($prestation, $examen)->elementPaillasse->unit }}
                                    </p>
                                </td>

                                <td class="border-start-0 border-end-0 border-top-0 text-center" style="border-style: dotted;; font-family: 'Times New Roman', serif;">
                                    @foreach($anteriorities as $anteriority)
                                        @if($anteriority['element_paillasse_id'] == showResultExamen($prestation, $examen)->elementPaillasse->id)
                                            <div class="fst-italic" style="font-size: 0.8rem">
                                                {{ $anteriority->result->result_client }} {{ showResultExamen($prestation, $examen)->elementPaillasse->unit }} ({{ $anteriority->result->created_at->format('d/m/Y') }})
                                            </div>
                                        @endif
                                    @endforeach
                                </td>

                                <td class="border-start-0 border-end-0 border-top-0 text-center" style="border-style: dotted;">
                                    @foreach(showResultExamen($prestation, $examen)->elementPaillasse->group_populations as $population)
                                        @if($population->sex_id == $prestation->client->sexe_id && ($population->agemin <= $prestation->client->age * 12 && $population->agemax >= $prestation->client->age * 12))
                                            @if($population->pivot->sign == '[]')
                                                {{ $population->pivot->value }} - {{ $population->pivot->value_max }}
                                            @else
                                                {{ $population->pivot->sign }} {{ $population->pivot->value }}
                                            @endif
                                        @endif
                                    @endforeach
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td colspan="5" class="border-start-0 border-end-0 border-top-0 fw-bold" style="border-style: dotted;">
                                    {{ $examen->name }}
                                </td>
                            </tr>
                        @endif

                        @foreach($examen->elementPaillasses->sortBy('numero_order') as $index => $elementPaillasse)
                            @if(showResult($prestation, $elementPaillasse, $examen) && $elementPaillasse->typeResult->afficher_result)
                                @if($elementPaillasse->typeResult->type == 'inline')
                                    <tr>
                                        <td colspan="5" class="border-0" style=""></td>
                                    </tr>
                                @endif

                                @if($elementPaillasse->typeResult->type == 'group')
                                    <tr style="font-family: Arial, serif;">
                                        <td colspan="5" class="border-0" style="*padding-left: 1.2rem; padding-top: 0; padding-bottom: 0; padding-right: 0">
                                            <p class="fw-bold text-primary" style="margin: 0;">
                                                {{ $elementPaillasse->name }}
                                            </p>
                                        </td>
                                    </tr>
                                @endif

                                @if($elementPaillasse->typeResult->type == 'comment')
                                    <tr style="font-family: Arial, serif;">
                                        <td colspan="5" class="" style="padding-left: {{ ($elementPaillasse->indent == 0 ? 1 : $elementPaillasse->indent * 1.2) * 1.1 }}rem; padding-top: 0; padding-bottom: 0; padding-right: 0">
                                            <div class="w-50">
                                                @foreach($prestation->results as $result)
                                                    @if($result->element_paillasse_id == $elementPaillasse->id && $result->prestation_id == $prestation->id)
                                                        <p class="fw-bold text-primary" style="padding: 0; margin: 0;">
                                                            {{ $result->result_machine }}
                                                        </p>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                @endif

                                @if (!($elementPaillasse->typeResult->type == 'group' || $elementPaillasse->typeResult->type == 'inline' || $elementPaillasse->typeResult->type == 'comment'))
                                    <tr style="font-family: Arial, serif;">
                                        <td class="border-0" style="padding-left: {{ ($elementPaillasse->indent == 0 ? 1 : $elementPaillasse->indent * 1.2) * 1.1 }}rem; padding-top: 0; padding-bottom: 0; padding-right: 0">
                                            @if(!$elementPaillasse->hide_label)
                                                <p style="margin: 0;">{{ $elementPaillasse->name }}</p>
                                            @endif
                                        </td>

                                        @foreach($prestation->results as $result)
                                            @if($result->element_paillasse_id == $elementPaillasse->id && $result->prestation_id == $prestation->id)
                                                <td class="border-0" style="padding: 0">
                                                    <span class="" style="font-weight: 500">{{ $result->result_client }} {{ $elementPaillasse->unit }}</span>
                                                </td>

                                                <td class="border-0 text-center" style="; font-family: 'Times New Roman', serif;" style="padding: 0">
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

                                        <td class="border-0 text-center" style="padding:0;">
                                            @foreach($elementPaillasse->group_populations as $population)
                                                @if($population->sex_id == $prestation->client->sexe_id && ($population->agemin <= $prestation->client->age * 12 && $population->agemax >= $prestation->client->age * 12))
                                                    @if($population->pivot->sign == '[]')
                                                        {{ $population->pivot->value }} - {{ $population->pivot->value_max }}
                                                    @else
                                                        {{ $population->pivot->sign }} {{ $population->pivot->value }}
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
            @endforeach
        </tbody>
    </table>

</div>

</body>

</html>
