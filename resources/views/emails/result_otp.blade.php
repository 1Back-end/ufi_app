<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; color: #333; padding: 20px; }
        .container { max-width: 600px; background: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .code-box { background: #eef2f7; font-size: 28px; font-weight: bold; text-align: center; letter-spacing: 5px; padding: 15px; margin: 20px 0; border-radius: 6px; color: #0d6efd; }
        .footer { font-size: 12px; color: #777; margin-top: 30px; text-align: center; }
    </style>
</head>
<body>
<div class="container">
    <h2>Sécurité de vos résultats médicaux</h2>
    <p>Bonjour,</p>
    <p>Vous avez demandé l'accès à vos résultats d'examens médicaux. Veuillez utiliser le code de vérification ci-dessous pour déverrouiller l'accès :</p>

    <div class="code-box">{{ $otpCode }}</div>

    <p><strong>Ce code est valide pendant 15 minutes.</strong> Ne le partagez avec personne.</p>

    <div class="footer">
        &copy; {{ date('Y') }} GT LABO - Tous droits réservés.
    </div>
</div>
</body>
</html>
