<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajustement de facture</title>
    <!-- Importation de la police moderne 'Inter' -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #334155;
            line-height: 1.6;
            background-color: #f1f5f9;
            margin: 0;
            padding: 40px 20px;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
        }
        .email-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 30px;
            text-align: center;
        }
        .email-header h2 {
            color: #ffffff;
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            letter-spacing: -0.025em;
        }
        .email-body {
            padding: 35px 30px;
        }
        .email-body p {
            margin: 0 0 16px 0;
            color: #475569;
            font-size: 15px;
        }
        .invoice-badge {
            display: inline-block;
            background-color: #e0f2fe;
            color: #0369a1;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 4px;
        }
        .details-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #0284c7;
            border-radius: 8px;
            padding: 20px;
            margin: 24px 0;
        }
        .details-card p {
            color: #1e293b;
            font-size: 14px;
        }
        .amount-list {
            list-style: none;
            padding: 0;
            margin: 12px 0 0 0;
        }
        .amount-list li {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #e2e8f0;
            font-size: 14px;
            color: #334155;
        }
        .amount-list li:last-child {
            border-bottom: none;
        }
        .amount-value {
            font-weight: 600;
            color: #0f172a;
        }
        .action-container {
            text-align: center;
            margin: 35px 0 20px 0;
        }
        .btn-primary {
            background-color: #0284c7;
            color: #ffffff !important;
            padding: 12px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: inline-block;
            box-shadow: 0 4px 6px -1px rgba(2, 132, 199, 0.2);
            transition: background-color 0.2s;
        }
        .btn-primary:hover {
            background-color: #0369a1;
        }
        .email-footer {
            font-size: 12px;
            color: #94a3b8;
            text-align: center;
            background-color: #f8fafc;
            border-top: 1px solid #f1f5f9;
            padding: 20px 30px;
            margin-top: 20px;
        }
        .email-footer p {
            margin: 0;
            color: #94a3b8;
        }
    </style>
</head>
<body>
<div class="email-wrapper">
    <div class="email-header">
        <h2>Mise à jour d'une facture</h2>
    </div>

    <div class="email-body">
        <p>Bonjour,</p>

        <p>Nous vous informons qu'un ajustement tarifaire vient d'être appliqué sur la facture <span class="invoice-badge">n° {{ $facture->code }}</span> @if(!empty($facture->prestation_id)) (Prestation n° {{ $facture->prestation_id }}) @endif.</p>

        <div class="details-card">
            <p style="margin: 0 0 12px 0;"><strong>Auteur de la modification :</strong> {{ $userName }}</p>
            <p style="margin: 0 0 4px 0;"><strong>Détails des montants mis à jour :</strong></p>

            <ul class="amount-list">
                <li>
                    <span>Part Assurance :</span>
                    <span class="amount-value">{{ \App\Helpers\FormatPrice::format($facture->amount_insurance) }}</span>
                </li>
                <li>
                    <span>Part Client / Assuré :</span>
                    <span class="amount-value">{{ \App\Helpers\FormatPrice::format($facture->amount_insured) }}</span>
                </li>
            </ul>
        </div>

        <p>Vous pouvez consulter l'historique complet ainsi que les justificatifs associés en vous connectant à votre espace de gestion.</p>

    </div>

    <div class="email-footer">
        <p>Ceci est un message automatique généré par l'application, merci de ne pas y répondre directement.<br>
            <strong>GT LABO</strong> &copy; {{ date('Y') }} — Tous droits réservés.</p>
    </div>
</div>
</body>
</html>
