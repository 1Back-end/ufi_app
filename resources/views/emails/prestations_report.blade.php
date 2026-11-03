<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport d'activités - {{ $data['centre_name'] }}</title>
    <!-- Import de la police Rubik -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Styles Responsive pour clients mail mobiles */
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                border-radius: 0 !important;
                border: none !important;
            }
            .content-padding {
                padding: 20px 16px !important;
            }
            .header-padding {
                padding: 24px 16px !important;
            }
            .stack-column {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                direction: ltr !important;
            }
            .stack-spacer {
                display: block !important;
                height: 12px !important;
                width: 100% !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: 'Rubik', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1e293b; -webkit-font-smoothing: antialiased;">

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 20px 0;">
    <tr>
        <td align="center">
            <!-- Conteneur principal (max-width 580px, responsive sur mobile) -->
            <table role="presentation" class="email-container" width="580" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04); border: 1px solid #e2e8f0; width: 100%; max-width: 580px;">

                <!-- En-tête -->
                <tr>
                    <td class="header-padding" style="background: linear-gradient(135deg, #0e7490 0%, #155e75 100%); padding: 28px 24px; text-align: center;">
                        <div style="display: inline-block; background-color: rgba(255, 255, 255, 0.15); padding: 4px 14px; border-radius: 20px; font-size: 11px; font-weight: 600; color: #cffafb; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 8px;">
                            {{ $data['centre_name'] }}
                        </div>
                        <h2 style="color: #ffffff; margin: 0; font-size: 20px; font-weight: 700; letter-spacing: -0.3px;">
                            Rapport Journalier d'Activités
                        </h2>
                        <p style="color: #a5f3fc; margin: 6px 0 0 0; font-size: 13px; font-weight: 400;">
                            Journée du <strong>{{ $data['centre_name'] }}</strong>
                        </p>
                    </td>
                </tr>

                <!-- Contenu principal -->
                <tr>
                    <td class="content-padding" style="padding: 28px 24px;">
                        <!-- Texte d'introduction -->
                        <p style="font-size: 12px; color: #475569; margin-top: 0; margin-bottom: 20px; line-height: 1.5;">
                            Bonjour,<br>
                            Voici le récapitulatif synthétique des activités enregistrées aujourd'hui dans le système <strong>GT LABO</strong> :
                        </p>

                        <!-- Bloc Chiffres Clés Totaux (Responsive empilable) -->
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 20px;">
                            <tr>
                                <td align="center">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <!-- Carte Total Prestations -->
                                            <td class="stack-column" width="48%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px 12px; text-align: center; vertical-align: top;">
                                                <span style="display: block; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Total Prestations</span>
                                                <span style="display: block; font-size: 22px; font-weight: 700; color: #0e7490; margin-top: 4px;">
                                                    {{ $data['total_prestations'] }}
                                                </span>
                                            </td>

                                            <!-- Espace horizontal entre les cartes sur desktop -->
                                            <td class="stack-spacer" width="4%" style="font-size: 0; line-height: 0;">&nbsp;</td>

                                            <!-- Carte Montant Total -->
                                            <td class="stack-column" width="48%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px 12px; text-align: center; vertical-align: top;">
                                                <span style="display: block; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Montant Total</span>
                                                <span style="display: block; font-size: 15px; font-weight: 700; color: #0e7490; margin-top: 6px; line-height: 1.2; word-break: break-word;">
                                                    {{ \App\Helpers\FormatPrice::format($data['montant_total']) }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <!-- Tableau de Répartition des Prestations -->
                        <div style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; margin-bottom: 18px;">
                            <div style="background-color: #f8fafc; padding: 10px 14px; border-bottom: 1px solid #e2e8f0;">
                                <span style="font-size: 11px; font-weight: 700; color: #0e7490; text-transform: uppercase; letter-spacing: 0.5px;">
                                    Détail de la Répartition des Prestations
                                </span>
                            </div>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse;">
                                <thead>
                                <tr style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                    <th align="left" style="padding: 8px 14px; font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">Catégorie</th>
                                    <th align="center" style="padding: 8px 14px; font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">Nbr</th>
                                    <th align="right" style="padding: 8px 14px; font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">Montant Total</th>
                                </tr>
                                </thead>
                                <tbody>
                                <!-- Paiements Comptants -->
                                <tr>
                                    <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; font-size: 11px; color: #475569;">
                                        <i class="fa-solid fa-money-bill-wave" style="color: #0e7490; margin-right: 6px; font-size: 11px;"></i>
                                        Comptants
                                    </td>
                                    <td align="center" style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; font-size: 11px; font-weight: 600; color: #0f172a;">
                                        {{ $data['count_comptant'] }}
                                    </td>
                                    <td align="right" style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; font-size: 11px; font-weight: 700; color: #0e7490;">
                                        {{ \App\Helpers\FormatPrice::format($data['amount_comptant']) }}
                                    </td>
                                </tr>

                                <!-- Prises en charge / Assurances -->
                                <tr>
                                    <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; font-size: 11px; color: #475569;">
                                        <i class="fa-solid fa-hospital-user" style="color: #0e7490; margin-right: 6px; font-size: 11px;"></i>
                                        Assurances
                                    </td>
                                    <td align="center" style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; font-size: 11px; font-weight: 600; color: #0f172a;">
                                        {{ $data['count_assurance'] }}
                                    </td>
                                    <td align="right" style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; font-size: 11px; font-weight: 700; color: #0e7490;">
                                        {{ \App\Helpers\FormatPrice::format($data['amount_assurance']) }}
                                    </td>
                                </tr>

                                <!-- Clients Associés -->
                                <tr>
                                    <td style="padding: 10px 14px; font-size: 11px; color: #475569;">
                                        <i class="fa-solid fa-handshake" style="color: #0e7490; margin-right: 6px; font-size: 11px;"></i>
                                        Clients Associés
                                    </td>
                                    <td align="center" style="padding: 10px 14px; font-size: 11px; font-weight: 600; color: #0f172a;">
                                        {{ $data['count_client_associe'] }}
                                    </td>
                                    <td align="right" style="padding: 10px 14px; font-size: 11px; font-weight: 700; color: #0e7490;">
                                        {{ \App\Helpers\FormatPrice::format($data['amount_client_associe']) }}
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Tableau des Honoraires Consultants -->
                        <div style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; margin-bottom: 18px;">
                            <div style="background-color: #f8fafc; padding: 10px 14px; border-bottom: 1px solid #e2e8f0;">
                                <span style="font-size: 11px; font-weight: 700; color: #0e7490; text-transform: uppercase; letter-spacing: 0.5px;">
                                    Honoraires des Consultants
                                </span>
                            </div>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse;">
                                <thead>
                                <tr style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                    <th align="left" style="padding: 8px 14px; font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">Statut</th>
                                    <th align="center" style="padding: 8px 14px; font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">Nbr</th>
                                    <th align="right" style="padding: 8px 14px; font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">Montant Total</th>
                                </tr>
                                </thead>
                                <tbody>
                                <!-- Honoraires Payés -->
                                <tr>
                                    <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; font-size: 11px; color: #475569;">
                                        <i class="fa-solid fa-circle-check" style="color: #0e7490; margin-right: 6px; font-size: 11px;"></i>
                                        Payés
                                    </td>
                                    <td align="center" style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; font-size: 11px; font-weight: 600; color: #0f172a;">
                                        {{ $data['count_consultant_paid'] }}
                                    </td>
                                    <td align="right" style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; font-size: 11px; font-weight: 700; color: #0e7490;">
                                        {{ \App\Helpers\FormatPrice::format($data['amount_consultant_paid']) }}
                                    </td>
                                </tr>

                                <!-- Honoraires En Attente -->
                                <tr>
                                    <td style="padding: 10px 14px; font-size: 11px; color: #475569;">
                                        <i class="fa-solid fa-clock" style="color: #0e7490; margin-right: 6px; font-size: 11px;"></i>
                                        En attente de paiement
                                    </td>
                                    <td align="center" style="padding: 10px 14px; font-size: 11px; font-weight: 600; color: #0f172a;">
                                        {{ $data['count_consultant_pending'] }}
                                    </td>
                                    <td align="right" style="padding: 10px 14px; font-size: 11px; font-weight: 700; color: #0e7490;">
                                        {{ \App\Helpers\FormatPrice::format($data['amount_consultant_pending']) }}
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; margin-bottom: 18px;">
                            <div style="background-color: #f8fafc; padding: 10px 14px; border-bottom: 1px solid #e2e8f0;">
                                <span style="font-size: 11px; font-weight: 700; color: #0e7490; text-transform: uppercase; letter-spacing: 0.5px;">
                                    Suivi Imagerie & Bilans
                                </span>
                            </div>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse;">
                                <tbody>
                                <!-- Prestations Imagerie -->
                                <tr>
                                    <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; font-size: 11px; color: #475569;">
                                        <i class="fa-solid fa-x-ray" style="color: #0e7490; margin-right: 6px; font-size: 11px;"></i>
                                        Prestations Imagerie
                                    </td>
                                    <td align="right" style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; font-size: 11px; font-weight: 700; color: #0e7490;">
                                        {{ $data['total_prestations_imagerie'] }}
                                    </td>
                                </tr>
                                <!-- Bilans Établis -->
                                <tr>
                                    <td style="padding: 10px 14px; font-size: 11px; color: #475569;">
                                        <i class="fa-solid fa-file-medical" style="color: #0e7490; margin-right: 6px; font-size: 11px;"></i>
                                        Total Bilans Établis
                                    </td>
                                    <td align="right" style="padding: 10px 14px; font-size: 11px; font-weight: 700; color: #0e7490;">
                                        {{ $data['total_bilans'] }}
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Bloc Nouveaux Patients Enregistrés -->
                        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; margin-bottom: 24px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="font-size: 11px; font-weight: 600; color: #475569;">
                                        <i class="fa-solid fa-user-plus" style="color: #0e7490; margin-right: 8px; font-size: 12px;"></i>
                                        Nouveaux Patients Enregistrés
                                    </td>
                                    <td align="right" style="font-size: open; font-size: 14px; font-weight: 700; color: #0e7490;">
                                        {{ $data['count_clients_created'] }}
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <p style="font-size: 12px; color: #475569; margin-bottom: 0; line-height: 1.5;">
                            Cordialement,<br>
                            <strong style="color: #0f172a;">L'équipe {{ $data['centre_name'] }}</strong>
                        </p>
                    </td>
                </tr>

                <!-- Pied de page -->
                <tr>
                    <td style="background-color: #f8fafc; padding: 16px; text-align: center; border-top: 1px solid #f1f5f9;">
                        <p style="font-size: 11px; color: #94a3b8; margin: 0; font-weight: 400;">
                            Cet e-mail est généré automatiquement par l'application {{ $data['centre_name'] }}.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
