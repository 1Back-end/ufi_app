<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vos résultats de laboratoire - GT LABO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body, table, td, h2, p, span {
            font-family: 'Rubik', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif !important;
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; -webkit-font-smoothing: antialiased;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 40px 10px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 580px; background-color: #ffffff; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08); overflow: hidden; border: 1px solid #e2e8f0;">

                <!-- En-tête -->
                <tr>
                    <td style="padding: 28px 40px; background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                            <tr>
                                <td>
                                        <span style="font-size: 22px; font-weight: 700; color: #ffffff; letter-spacing: -0.5px;">
                                            GT <span style="font-weight: 400; opacity: 0.9;">LABO</span>
                                        </span>
                                </td>
                                <td align="right">
                                        <span style="background-color: rgba(255, 255, 255, 0.2); color: #ffffff; font-size: 11px; font-weight: 600; padding: 6px 12px; border-radius: 20px; text-transform: uppercase;">
                                            Confidentiel
                                        </span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Corps -->
                <tr>
                    <td style="padding: 40px;">
                        <h2 style="margin: 0 0 12px 0; font-size: 22px; font-weight: 600; color: #0f172a;">
                            Bonjour {{ $client->nomcomplet_client }},
                        </h2>

                        <p style="margin: 0 0 24px 0; font-size: 15px; color: #475569; line-height: 1.6;">
                            Vos résultats d'examens médicaux sont disponibles. Vous trouverez ci-joint le compte-rendu officiel au format PDF.
                        </p>

                        <!-- Alerte / Code PIN -->
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 12px; margin-bottom: 24px;">
                            <tr>
                                <td align="center" style="padding: 24px 20px;">
                                        <span style="display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; margin-bottom: 8px; letter-spacing: 1px;">
                                            Mot de passe du fichier PDF
                                        </span>
                                    <div style="background-color: #0f172a; color: #38bdf8; display: inline-block; padding: 10px 24px; border-radius: 8px;">
                                        <span style="font-size: 28px; font-weight: 700; letter-spacing: 6px; font-family: monospace;">{{ $codePin }}</span>
                                    </div>
                                    <span style="display: block; font-size: 12px; color: #94a3b8; margin-top: 10px;">
                                            Ce code vous sera demandé à l'ouverture du PDF.
                                        </span>
                                </td>
                            </tr>
                        </table>

                        <p style="margin: 0; font-size: 13px; color: #64748b; line-height: 1.5;">
                            Pour des raisons de sécurité, ce document est crypté. Assurez-vous de saisir votre code PIN à 4 chiffres lorsque votre lecteur PDF vous le demandera sur votre téléphone ou ordinateur.
                        </p>
                    </td>
                </tr>

                <!-- Pied de page -->
                <tr>
                    <td align="center" style="padding: 24px 40px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 12px;">
                        <p style="margin: 0 0 4px 0; font-weight: 500; color: #64748b;">GT LABO &mdash; Laboratoire d'Analyses Médicales</p>
                        <p style="margin: 0;">&copy; {{ date('Y') }} Tous droits réservés.</p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
