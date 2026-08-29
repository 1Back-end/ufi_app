<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>LISTE DES FOURNISSEURS - {{ $centre->name ?? '' }}</title>

    <style>
        {!! $bootstrap !!}
    </style>

    <style>
        @page {
            size: A4 landscape;
            margin: 8mm;
            counter-reset: page;
        }

        body, html {
            height: 100%;
            margin: 0;
            padding: 0;
            font-size: 2.2mm !important;
            font-family: "Times New Roman", serif;
        }

        .print-wrapper {
            position: relative;
        }

        h1 {
            font-size: 4mm !important;
        }

        table {
            page-break-inside: auto;
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            display: table-header-group;
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

        th, td {
            padding: 4px 6px !important;
            vertical-align: middle;
        }
    </style>
</head>
<body>

<div class="col-lg-12 col-sm-12 p-0 print-wrapper">

    <header class="d-flex align-items-center size" style="font-family: 'Times New Roman', serif">
        <div class="w-25">
            @if(!empty($logo) && file_exists(public_path($logo)))
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path($logo))) }}" alt="" class="img-fluid w-50">
            @endif
        </div>

        <div class="text-center w-75" style="line-height: 16px">
            <div class="fs-3 text-uppercase fw-bold">
                {{ $centre->name ?? '' }}
            </div>

            <div>
                - {{ $centre->address ?? '' }} - {{ $centre->town ?? '' }}
            </div>

            <div>
                BP: {{ $centre->postal_code ?? '' }} {{ $centre->town ?? '' }} -
                Tél. {{ $centre->tel ?? '' }} {{ $centre->tel2 ? '/' . $centre->tel2 : '' }}
                @if(!empty($centre->fax)) / Fax: {{ $centre->fax }} @endif
            </div>

            <div>
                Email: {{ $centre->email ?? '' }}
            </div>

            <div>
                Autorisation n° {{ $centre->autorisation ?? '' }} | NIU: {{ $centre->contribuable ?? '' }}
            </div>
        </div>
    </header>

    <div class="mt-2 w-100" style="border-top: 1px double rgb(0, 0, 0, 0.75); margin-bottom: 2px"></div>
    <div class="mb-2 w-100" style="border-top: 1px double rgb(0, 0, 0, 0.75);"></div>

    <h1 class="fs-3 fw-bold text-center text-uppercase my-3">
        LISTING DES FOURNISSEURS
    </h1>

    <p class="fst-italic text-end mb-2">Date d'impression : {{ now()->format('d/m/Y H:i') }}</p>

    <table class="table table-bordered table-striped" style="font-size: 8.5px;">
        <thead>
        <tr>
            <th>#</th>
            <th>Nom complet</th>
            <th>Entreprise</th>
            <th>Adresse</th>
            <th>Téléphone</th>
            <th>Email</th>
            <th>N° Contribuable</th>
            <th>N° RCCM</th>
            <th>Ville</th>
            <th>Pays</th>
            <th>Contact</th>
            <th>Tél. Contact</th>
            <th>Créé le</th>
            <th>Par</th>
        </tr>
        </thead>
        <tbody>
        @forelse($fournisseurs as $index => $fournisseur)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $fournisseur->full_name ?? '' }}</td>
                <td>{{ $fournisseur->company_name ?? '' }}</td>
                <td>{{ $fournisseur->address ?? '' }}</td>
                <td>{{ $fournisseur->phone_number }}{{ $fournisseur->second_phone_number ? ' ' . $fournisseur->second_phone_number : '' }}</td>
                <td>{{ $fournisseur->email ?? '' }}</td>
                <td>{{ $fournisseur->tax_number ?? '' }}</td>
                <td>{{ $fournisseur->business_registration_number ?? '' }}</td>
                <td>{{ $fournisseur->website ?? '' }}</td>
                <td>{{ $fournisseur->city ?? '' }}</td>
                <td>{{ $fournisseur->country ?? '' }}</td>
                <td>{{ $fournisseur->contact_person ?? '' }}</td>
                <td>{{ $fournisseur->contact_person_phone ?? '' }}</td>
                <td class="text-center">{{ $fournisseur->is_active ? 'Actif' : 'Inactif' }}</td>
                <td class="text-center">{{ $fournisseur->created_at?->format('d/m/Y H:i') }}</td>
                <td>{{ $fournisseur->creator?->nom_utilisateur ?? '' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="16" class="text-center">Aucun fournisseur trouvé</td>
            </tr>
        @endforelse
        </tbody>
    </table>

</div>

</body>
</html>
