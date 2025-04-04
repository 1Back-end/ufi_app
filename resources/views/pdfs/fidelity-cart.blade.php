<!DOCTYPE html>
<html>
<head>
    <title>Carte de fidélité</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            color: white;
        }
        body {
            font-family: 'Arial, sans-serif', serif;
            background: center / cover no-repeat url("data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/background-fidelity-card.png'))) }}");
        }
        .container {
            margin: 0 auto;
            padding: 20px;
            text-align: center;
        }
        .header {
            margin-bottom: 20px;
        }
        .header h1 {
            text-transform: uppercase;
        }
        .fidelity-table {
            display: table;
            margin: 20px auto;
            border-spacing: 10px;
        }
        .fidelity-table td {
            width: 5.2rem;
            height: 5.2rem;
            background-color: white;
            border-radius: 50%;
        }
        .gift {
            position: relative;
            text-align: center;
            background-color: white;
        }
        .gift > img {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-60%, -65%);
        }
        .footer {
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Carte de fidélité</h1>
        <p>{{ $client->nomcomplet_client }}</p>
    </div>
    <table class="fidelity-table">
        <tr>
            <td></td> <td></td> <td></td> <td></td> <td></td>
        </tr>
        <tr>
            <td></td> <td></td> <td></td> <td></td> <td class="gift"><img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/gift.svg'))) }}" alt="" width="50" height="50"></td>
        </tr>
    </table>
    <div class="footer">
        <p>Consulter 9 fois, Et la 10e est offerte!</p>
        <p>Valide jusqu'en {{ now()->addDays($validity)->format('d/m/y') }}</p>
    </div>
</div>
</body>
</html>
