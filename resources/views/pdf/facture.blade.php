<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }

  .page { padding: 26px 30px 50px 30px; }

  /* ── En-tête ── */
  .header { width: 100%; border-bottom: 2px solid #f97316; padding-bottom: 12px; margin-bottom: 14px; overflow: hidden; }
  .header .left  { float: left; }
  .header .right { float: right; text-align: right; }
  .header h1  { font-size: 18px; font-weight: 700; color: #f97316; }
  .header p   { font-size: 10px; color: #64748b; margin-top: 3px; line-height: 1.5; }
  .num   { font-size: 14px; font-weight: 700; }
  .date  { font-size: 10px; color: #64748b; margin-top: 3px; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 8px; font-size: 9px; font-weight: 700; text-transform: uppercase; margin-top: 4px; }
  .badge-payee    { background: #dcfce7; color: #166534; }
  .badge-brouillon{ background: #fef9c3; color: #854d0e; }
  .badge-emise    { background: #dbeafe; color: #1d4ed8; }
  .badge-annulee  { background: #fee2e2; color: #991b1b; }
  .clear { clear: both; }

  /* ── Parties ── */
  .parties { overflow: hidden; margin-bottom: 12px; }
  .partie { float: left; width: 48%; background: #f8fafc; border-radius: 6px; padding: 9px 11px; }
  .partie.right-col { float: right; }
  .partie h4  { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #94a3b8; letter-spacing: .4px; margin-bottom: 4px; }
  .partie .name { font-weight: 700; font-size: 12px; }
  .partie p { font-size: 11px; line-height: 1.5; }

  /* ── Barre réservation ── */
  .resa-bar { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 6px; padding: 7px 10px; margin-bottom: 12px; overflow: hidden; }
  .resa-item { float: left; padding-right: 18px; }
  .resa-item label { font-size: 9px; color: #9a3412; text-transform: uppercase; font-weight: 700; display: block; }
  .resa-item span  { font-size: 11px; font-weight: 600; }

  /* ── Tableau lignes ── */
  table.lines { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
  table.lines thead tr { background: #1e293b; color: #fff; }
  table.lines thead th { padding: 6px 9px; font-size: 10px; font-weight: 600; text-align: left; }
  table.lines thead th.r { text-align: right; }
  table.lines tbody tr:nth-child(even) { background: #f8fafc; }
  table.lines tbody td { padding: 5px 9px; font-size: 11px; border-bottom: 1px solid #e2e8f0; }
  table.lines tbody td.r { text-align: right; }

  /* ── Totaux (float right) ── */
  .totaux-wrap { overflow: hidden; margin-bottom: 10px; }
  .paiement-box { float: left; width: 48%; background: #f0fdf4; border: 1px solid #86efac; border-radius: 6px; padding: 8px 11px; font-size: 11px; }
  .paiement-box strong { color: #166534; }
  table.totaux { float: right; width: 240px; border-collapse: collapse; }
  table.totaux td { padding: 5px 9px; font-size: 11px; }
  table.totaux tr.ht  td { background: #f8fafc; }
  table.totaux tr.tva td { background: #f1f5f9; }
  table.totaux tr.ttc td { background: #f97316; color: #fff; font-size: 13px; font-weight: 700; }
  table.totaux td.r { text-align: right; }

  /* ── Footer ── */
  .footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 8px 30px;
    border-top: 1px solid #e2e8f0;
    text-align: center;
    font-size: 9px;
    color: #94a3b8;
    background: #fff;
  }
</style>
</head>
<body>
<div class="page">

  {{-- En-tête --}}
  <div class="header">
    <div class="left">
      @if($etablissement->logo_url)
        <img src="{{ public_path('storage/' . ltrim(str_replace('/storage/', '', parse_url($etablissement->logo_url, PHP_URL_PATH)), '/')) }}" height="36" style="margin-bottom:3px;" /><br/>
      @endif
      <h1>{{ $etablissement->nom }}</h1>
      <p>
        @if($etablissement->adresse){{ $etablissement->adresse }}<br/>@endif
        @if($etablissement->telephone)Tél : {{ $etablissement->telephone }}  @endif
        @if($etablissement->email){{ $etablissement->email }}@endif
        @if($etablissement->numero_fiscal)<br/>NIF : {{ $etablissement->numero_fiscal }}@endif
      </p>
    </div>
    <div class="right">
      <div class="num">FACTURE #{{ $facture->numero_facture }}</div>
      <div class="date">Émise le {{ $facture->date_emission?->format('d/m/Y') }}</div>
      @if($facture->date_paiement)
        <div class="date">Payée le {{ $facture->date_paiement->format('d/m/Y') }}</div>
      @endif
      <div><span class="badge badge-{{ $facture->statut }}">{{ ucfirst($facture->statut) }}</span></div>
    </div>
    <div class="clear"></div>
  </div>

  {{-- Parties --}}
  <div class="parties">
    <div class="partie">
      <h4>Établissement</h4>
      <p class="name">{{ $etablissement->nom }}</p>
      @if($etablissement->adresse)<p>{{ $etablissement->adresse }}</p>@endif
      @if($etablissement->numero_registre)<p>RC : {{ $etablissement->numero_registre }}</p>@endif
    </div>
    <div class="partie right-col">
      <h4>Client</h4>
      @if($facture->client)
        <p class="name">{{ $facture->client->prenom }} {{ $facture->client->nom }}</p>
        @if($facture->client->email)<p>{{ $facture->client->email }}</p>@endif
        @if($facture->client->telephone)<p>{{ $facture->client->telephone }}</p>@endif
      @else
        <p class="name">Client de passage</p>
      @endif
    </div>
    <div class="clear"></div>
  </div>

  {{-- Info réservation --}}
  @if($facture->reservation)
  <div class="resa-bar">
    <div class="resa-item"><label>Réservation</label><span>{{ $facture->reservation->code_confirmation }}</span></div>
    <div class="resa-item"><label>Chambre</label><span>N° {{ $facture->reservation->chambre->numero ?? '—' }}</span></div>
    <div class="resa-item"><label>Arrivée</label><span>{{ $facture->reservation->date_arrivee?->format('d/m/Y H:i') }}</span></div>
    <div class="resa-item"><label>Départ</label><span>{{ $facture->reservation->date_depart?->format('d/m/Y H:i') }}</span></div>
    <div class="resa-item"><label>Durée</label><span>{{ $facture->reservation->duree_libelle }}</span></div>
    <div class="clear"></div>
  </div>
  @endif

  {{-- Lignes --}}
  <table class="lines">
    <thead>
      <tr>
        <th style="width:42%">Description</th>
        <th>Catégorie</th>
        <th class="r">Qté</th>
        <th class="r">Prix unit. ({{ $devise }})</th>
        <th class="r">Montant ({{ $devise }})</th>
      </tr>
    </thead>
    <tbody>
      @forelse($facture->ligneFactures as $ligne)
      <tr>
        <td>{{ $ligne->description }}</td>
        <td>{{ $ligne->categorie ?? '—' }}</td>
        <td class="r">{{ $ligne->quantite }}</td>
        <td class="r">{{ number_format($ligne->prix_unitaire, 0, ',', ' ') }}</td>
        <td class="r">{{ number_format($ligne->montant_total, 0, ',', ' ') }}</td>
      </tr>
      @empty
      <tr><td colspan="5" style="text-align:center;color:#94a3b8;font-style:italic;padding:8px">Aucune ligne</td></tr>
      @endforelse
    </tbody>
  </table>

  {{-- Totaux + paiement --}}
  <div class="totaux-wrap">
    @if($facture->statut === 'payee' && $facture->mode_paiement)
    <div class="paiement-box">
      <strong>Payé par :</strong> {{ ucfirst(str_replace('_', ' ', $facture->mode_paiement)) }}
      @if($facture->date_paiement) — le {{ $facture->date_paiement->format('d/m/Y') }}@endif
    </div>
    @endif
    <table class="totaux">
      <tr class="ht" ><td>Montant HT</td><td class="r">{{ number_format($facture->montant_ht,  0, ',', ' ') }} {{ $devise }}</td></tr>
      <tr class="tva"><td>TVA ({{ $tvaRate }} %)</td><td class="r">{{ number_format($facture->tva, 0, ',', ' ') }} {{ $devise }}</td></tr>
      <tr class="ttc"><td>Total TTC</td><td class="r">{{ number_format($facture->montant_ttc, 0, ',', ' ') }} {{ $devise }}</td></tr>
    </table>
    <div class="clear"></div>
  </div>

  {{-- Footer --}}
  <div class="footer">
    {{ $etablissement->nom }}@if($etablissement->adresse)  ·  {{ $etablissement->adresse }}@endif
    @if($etablissement->site_web)  ·  {{ $etablissement->site_web }}@endif
    <br/>Merci pour votre confiance.
  </div>

</div>
</body>
</html>
