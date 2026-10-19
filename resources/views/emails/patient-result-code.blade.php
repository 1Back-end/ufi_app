<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vos résultats de laboratoire - GT LABO</title>
    <!-- Importation de la police Rubik via Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body, table, td, h2, p, a, span {
            font-family: 'Rubik', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif !important;
        }
        @media only screen and (max-width: 620px) {
            .mobile-padding {
                padding: 24px 20px !important;
            }
            .mobile-title {
                font-size: 20px !important;
            }
            .code-text {
                font-size: 28px !important;
                letter-spacing: 6px !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; -webkit-font-smoothing: antialiased;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 40px 10px;">
    <tr>
        <td align="center">
            <!-- Conteneur principal -->
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 580px; background-color: #ffffff; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04); overflow: hidden; border: 1px solid #e2e8f0;">

                <!-- En-tête avec bandeau gradient médical -->
                <tr>
                    <td style="padding: 28px 40px; background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);" class="mobile-padding">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                            <tr>
                                <td>
                                        <span style="font-size: 22px; font-weight: 700; color: #ffffff; letter-spacing: -0.5px; display: inline-block;">
                                            GT <span style="font-weight: 400; opacity: 0.9;">LABO</span>
                                        </span>
                                </td>
                                <td align="right">
                                        <span style="background-color: rgba(255, 255, 255, 0.2); color: #ffffff; font-size: 11px; font-weight: 600; padding: 6px 12px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Sécurisé
                                        </span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Corps du message -->
                <tr>
                    <td style="padding: 40px;" class="mobile-padding">

                        <h2 style="margin: 0 0 12px 0; font-size: 22px; font-weight: 600; color: #0f172a; line-height: 1.3;" class="mobile-title">
                            Bonjour {{ $client->nomcomplet_client }},
                        </h2>

                        <p style="margin: 0 0 28px 0; font-size: 15px; color: #475569; line-height: 1.6;">
                            Vos résultats d'examens médicaux ont été validés par notre équipe biologique. Ils sont désormais prêts et consultables en toute confidentialité.
                        </p>

                        <!-- Carte Code de sécurité (Style coffre-fort) -->
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 12px; margin-bottom: 28px;">
                            <tr>
                                <td align="center" style="padding: 24px 20px;">
                                        <span style="display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; margin-bottom: 10px; letter-spacing: 1px;">
                                            Votre code d'accès confidentiel
                                        </span>
                                    <div style="background-color: #0f172a; color: #38bdf8; display: inline-block; padding: 10px 24px; border-radius: 8px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);">
                                        <span class="code-text" style="font-size: 32px; font-weight: 700; letter-spacing: 8px; font-family: 'Rubik', monospace;">{{ $code }}</span>
                                    </div>
                                    <span style="display: block; font-size: 12px; color: #94a3b8; margin-top: 10px;">
                                            A ne communiquer à personne
                                        </span>
                                </td>
                            </tr>
                        </table>

                        <p style="margin: 0 0 24px 0; font-size: 15px; color: #475569; line-height: 1.6;">
                            Pour déverrouiller votre document, cliquez sur le bouton ci-dessous et saisissez votre code :
                        </p>

                        <!-- Bouton Call to Action -->
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 32px;">
                            <tr>
                                <td align="center">
                                    <a href="{{ $secureLink }}" target="_blank" style="font-size: 16px; font-family: 'Rubik', Arial, sans-serif; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 10px; background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%); display: inline-block; font-weight: 600; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25); text-align: center;">
                                        Consulter mon compte-rendu
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <!-- Bloc avertissement expiration -->
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #fffbeb; border-left: 4px solid #f59e0b; border-radius: 4px; margin-bottom: 24px;">
                            <tr>
                                <td style="padding: 12px 16px;">
                                    <p style="margin: 0; font-size: 13px; color: #b45309; line-height: 1.5;">
                                        <strong>Note de sécurité :</strong> Ce lien et ce code sont à usage temporaire et expireront dans <strong>48 heures</strong>.
                                    </p>
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>

                <!-- Pied de page -->
                <tr>
                    <td align="center" style="padding: 24px 40px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 12px; line-height: 1.5;" class="mobile-padding">
                        <p style="margin: 0 0 6px 0; font-weight: 500; color: #64748b;">GT LABO &mdash; Système de transmission sécurisée</p>
                        <p style="margin: 0;">&copy; {{ date('Y') }} Tous droits réservés. Cet e-mail contient des informations confidentielles.</p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
