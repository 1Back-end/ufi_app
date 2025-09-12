<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>ORDONNANCES MEDICAL</title>
    <link rel="stylesheet" href="{{ public_path('certificats/style.css') }}">


    <style>
        {!! $bootstrap !!}
    </style>

    <style>
        body {
            font-size: 13px;
            font-family: "Rubik", sans-serif;
            margin: 10px;
        }
        .page-break {
            page-break-before: always;
        }
        footer {
            position: fixed;
            bottom: 10px;
            left: 0;
            right: 0;
            height: 40px;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            text-align: center;
            padding-top: 5px;
        }
        .page-number:before {
            content: "Page " counter(page);
        }
        /* Pour que la numérotation fonctionne */
        @page {
            margin: 15mm 10mm 20mm 10mm;
            counter-increment: page;
        }
    </style>
</head>
<body>
<div class="col-lg-12 col-sm-12 p-0">
    <div class="mb-2 d-flex justify-content-between align-items-center">
        <div class="mb-0 mx-2">
            <img src="{{ public_path('certificats/logo.png') }}" class="img-fluid" width="150" height="150">
        </div>
        <div>
            <small style="font-size: 9px;color: #00b050">
                Sis en face du Camp SIC Tsinga – Ouvert de Lundi à Samedi : 7H30 – 18H<br>
                B.P. 6107 Yaoundé - Tél : +237 653 01 01 / 691 53 42 28 / 691 53 03 21
            </small>
        </div>
    </div>


    {{-- Titre --}}
    <div class="mb-4 mt-3 text-center">
        <h4 class="text-uppercase" style="color: #00b050; font-size: 22px;">
            ORDONNANCE MÉDICALE
        </h4>
    </div>


    <div class="mt-4">
        <table class="table table-bordered table-striped text-center" style="font-size: 10px;">
            <thead>
            <tr>
                <th>#</th>
                <th>Produit</th>
                <th>Quantité</th>
                <th>Protocole de prise</th>
            </tr>
            </thead>
            <tbody>
            @foreach($data['ordonnance']->produits as $index => $produit)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $produit->nom }}</td>
                    <td>{{ $produit->quantite }}</td>
                    <td>{{ $produit->protocole }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    {{-- Signature --}}
    <div class="text-end mt-4" style="font-size: 13px;">
        <p>Fait à Yaoundé, le {{ $data['date_aujourdhui'] }}</p>
        <p style="color: #00b050;"><strong>Cachet et signature du prescipteur</strong></p>
    </div>

    {{-- Footer --}}
    <p class="text-start mt-4" style="font-size: 10px; color: #00b050">
        Votre santé est notre priorité
    </p>
</div>


</body>
</html>
