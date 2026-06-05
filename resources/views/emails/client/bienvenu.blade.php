<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Bienvenue</title>
  <style>
    body { margin: 0; padding: 0; background: #f8fafc; font-family: 'Segoe UI', Arial, sans-serif; color: #334155; }
    .wrapper { max-width: 560px; margin: 40px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.07); }
    .header { background: linear-gradient(135deg, #f97316, #f59e0b); padding: 36px 40px; text-align: center; }
    .header h1 { margin: 0; color: #fff; font-size: 24px; font-weight: 700; }
    .header p  { margin: 6px 0 0; color: rgba(255,255,255,.85); font-size: 14px; }
    .body { padding: 36px 40px; }
    .body p { line-height: 1.7; font-size: 15px; margin: 0 0 16px; }
    .creds { background: #f1f5f9; border-radius: 12px; padding: 20px 24px; margin: 24px 0; }
    .creds table { width: 100%; border-collapse: collapse; }
    .creds td { padding: 8px 0; font-size: 14px; vertical-align: top; }
    .creds td:first-child { color: #64748b; width: 130px; font-weight: 600; }
    .creds td:last-child  { color: #0f172a; font-weight: 700; word-break: break-all; }
    .notice { background: #fff7ed; border-left: 4px solid #f97316; border-radius: 0 8px 8px 0; padding: 12px 16px; font-size: 13px; color: #9a3412; margin: 20px 0 0; }
    .footer { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 40px; text-align: center; font-size: 12px; color: #94a3b8; }
  </style>
</head>
<body>
<div class="wrapper">

  <div class="header">
    <h1>{{ $etablissement->nom }}</h1>
    <p>Bienvenue dans votre espace client</p>
  </div>

  <div class="body">
    <p>Bonjour <strong>{{ $client->prenom }} {{ $client->nom }}</strong>,</p>

    <p>
      Votre compte client a été créé sur la plateforme <strong>{{ $etablissement->nom }}</strong>.
      Vous pouvez dès maintenant accéder à votre espace pour consulter vos réservations,
      passer des commandes et bien plus encore.
    </p>

    <div class="creds">
      <table>
        <tr>
          <td>Email</td>
          <td>{{ $client->email }}</td>
        </tr>
        <tr>
          <td>Mot de passe</td>
          <td>{{ $motDePasse }}</td>
        </tr>
      </table>
    </div>

    <div class="notice">
      Pour votre sécurité, nous vous recommandons de changer votre mot de passe dès votre première connexion.
    </div>

    <p style="margin-top:24px;">
      Nous sommes à votre disposition pour tout renseignement.<br />
      L'équipe {{ $etablissement->nom }}
    </p>
  </div>

  <div class="footer">
    © {{ date('Y') }} {{ $etablissement->nom }} — Tous droits réservés
    @if($etablissement->email)
      · <a href="mailto:{{ $etablissement->email }}" style="color:#f97316;">{{ $etablissement->email }}</a>
    @endif
  </div>

</div>
</body>
</html>
