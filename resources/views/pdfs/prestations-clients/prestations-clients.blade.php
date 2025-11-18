<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>ETATS JOURNALIERS DES CLIENTS</title>

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
        h1 {
            font-size: 5mm !important;
        }
        header {
            font-family: 'Helvetica', serif;
        }
        img {
            width: auto;
            height: auto;
        }

        .page-number:before {
            content: "Page " counter(page);
        }

        @page {
            margin: 15mm 10mm 20mm 10mm;
            counter-increment: page;
            size: A5;
        }

    </style>
</head>
<body>

<div class="col-lg-12 col-sm-12 p-0">


    <header class="d-flex align-items-center size" style="font-family: 'Times New Roman', serif">
        <div class="w-25">
            <img src="{{ public_path('certificats/logo.png') }}" alt=""
                 class="img-fluid w-50">
        </div>

        <div class="text-center" style="line-height: 18px">
            <div class="fs-3 text-uppercase fw-bold">
                CENTRE MEDICAL GT
            </div>

            <div class="">
                Sis en face du Camp SIC Tsinga – Ouvert de Lundi à Samedi : 7H30 – 18H<br>
                B.P. 6107 Yaoundé - Tél : +237 653 01 01 / 691 53 42 28 / 691 53 03 21<br>
                Boulevard du Sultan Njoya 2.351 / Email : cmgttsinga@yahoo.fr<br>
                Agrément N° 0708/A/MINSANTE/SG/DOSTS du 23 février 2021
            </div>


        </div>
    </header>

    <div class="mt-2 w-100" style="border-top: 1px double rgb(0, 0, 0, 0.75); margin-bottom: 2px"></div>
    <div class="mb-2 w-100" style="border-top: 1px double rgb(0, 0, 0, 0.75);"></div>


    <div class="mt-2 w-100">
        <table class="table table-bordered table-striped text-center" style="font-size: 2.5mm;">
            <thead>
            <th>#</th>
            <th>Patient</th>
            <th>Prescripteur</th>
            <th>Consultations/Actes</th>
            <th>PC</th>
            <th>Proforma</th>
            <th>PU</th>
            <th>Part patient</th>
            <th>Montant payé patient</th>
            <th>Montant prise en charges</th>
            <th>Assurance</th>
            <th>Création DT</th>
            </thead>
            <tbody>
            @foreach ($prestations as $index => $prestation)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $prestation->client->nomcomplet_client }}</td>
                    <td>{{ $prestation->consultant->nomcomplet ?? '-' }}</td>

                    <td>
                        @php
                            $actes = $prestation->actes->pluck('name')->toArray();
                            $consults = $prestation->consultations->pluck('name')->toArray();
                            $libelle = array_merge($actes, $consults);
                        @endphp
                        {{ implode(' + ', $libelle) }}
                    </td>

                    {{-- Prise en charge --}}
                    <td>{{ !empty($prestation->priseCharge->nomcomplet) ? 'Oui' : 'Non' }}</td>

                    {{-- Proforma (montant total estimé) --}}
                    <td>
                        Faux
                    </td>

                    <td>{{ $prestation->prestationables->pu }}</td>

                    {{-- Part patient --}}
                    <td>
                        {{ $prestation->factures->amount_client }}
                    </td>

                    {{-- Montant payé patient --}}
                    <td>
                        {{ $prestation->factures->amount_client }}
                    </td>

                    {{-- Montant prise en charge --}}
                    <td>
                        {{ $prestation->factures->amount_pc }}
                    </td>

                    {{-- Assurance --}}
                    <td>{{ $prestation->priseCharge->nom ?? '' }}</td>

                    {{-- Date création --}}
                    <td>{{ $prestation->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @endforeach
            </tbody>
            </tbody>
        </table>
    </div>




</div>

</body>
</html>
