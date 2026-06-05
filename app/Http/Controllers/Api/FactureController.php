<?php

namespace App\Http\Controllers\Api;

use App\Models\Etablissement;
use App\Models\Facture;
use App\Models\LigneFacture;
use App\Models\Reservation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class FactureController extends BaseController
{
    public function index(Request $request)
    {
        $query = Facture::where(function ($q) {
                $q->whereHas('reservation', fn($r) => $r->where('etablissement_id', $this->etabId()))
                  ->orWhereHas('commandeRestaurant', fn($r) => $r->where('etablissement_id', $this->etabId()));
            })
            ->with(['client', 'reservation', 'commandeRestaurant']);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('type_facture')) {
            $query->where('type_facture', $request->type_facture);
        }

        return $this->success($query->latest()->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
            'type_facture'   => 'nullable|in:sejour,restaurant,divers',
            'mode_paiement'  => 'nullable|in:especes,carte,virement,mobile_money',
            'lignes'         => 'required|array|min:1',
            'lignes.*.description'  => 'required|string',
            'lignes.*.categorie'    => 'nullable|string',
            'lignes.*.quantite'     => 'required|numeric|min:0.01',
            'lignes.*.prix_unitaire'=> 'required|numeric|min:0',
        ]);

        $reservation = Reservation::findOrFail($data['reservation_id']);
        if ($reservation->etablissement_id !== $this->etabId()) abort(403);

        // Calculer les montants
        $montantHt = collect($data['lignes'])->sum(fn($l) => $l['quantite'] * $l['prix_unitaire']);
        ['tva' => $tva, 'montantTtc' => $montantTtc] = $this->calculerTva($montantHt);

        $facture = Facture::create([
            'reservation_id'  => $data['reservation_id'],
            'client_id'       => $reservation->client_id,
            'numero_facture'  => 'FAC-' . date('Ymd') . '-' . str_pad(Facture::count() + 1, 4, '0', STR_PAD_LEFT),
            'type_facture'    => $data['type_facture'] ?? 'sejour',
            'statut'          => 'emise',
            'montant_ht'      => $montantHt,
            'tva'             => $tva,
            'montant_ttc'     => $montantTtc,
            'mode_paiement'   => $data['mode_paiement'] ?? null,
            'date_emission'   => now(),
        ]);

        foreach ($data['lignes'] as $ligne) {
            LigneFacture::create([
                'facture_id'      => $facture->id,
                'description'     => $ligne['description'],
                'categorie'       => $ligne['categorie'] ?? null,
                'quantite'        => $ligne['quantite'],
                'prix_unitaire'   => $ligne['prix_unitaire'],
                'montant_total'   => $ligne['quantite'] * $ligne['prix_unitaire'],
                'date_consommation' => now(),
            ]);
        }

        return $this->success($facture->load(['ligneFactures', 'client', 'reservation']), 'Facture créée.', 201);
    }

    public function show(Facture $facture)
    {
        $this->checkOwnership($facture);
        return $this->success($facture->load(['ligneFactures', 'client', 'reservation.chambre']));
    }

    public function payer(Request $request, Facture $facture)
    {
        $this->checkOwnership($facture);

        $data = $request->validate([
            'mode_paiement' => 'required|in:especes,carte,virement,mobile_money',
        ]);

        $facture->update([
            'statut'        => 'payee',
            'mode_paiement' => $data['mode_paiement'],
            'date_paiement' => now(),
        ]);

        return $this->success($facture, 'Facture marquée comme payée.');
    }

    public function destroy(Facture $facture)
    {
        $this->checkOwnership($facture);
        if ($facture->statut === 'payee') {
            return $this->error('Impossible de supprimer une facture payée.', 422);
        }
        $facture->update(['statut' => 'annulee']);
        return $this->success(null, 'Facture annulée.');
    }

    public function imprimer(Facture $facture)
    {
        $this->checkOwnership($facture);

        $facture->load(['ligneFactures', 'client', 'reservation.chambre', 'commandeRestaurant']);

        $etablissement = Etablissement::find($this->etabId());
        $devise        = $etablissement->devise?->code ?? 'FCFA';
        $tvaRate       = (float) ($etablissement->taux_tva ?? 0);

        $pdf = Pdf::loadView('pdf.facture', compact('facture', 'etablissement', 'devise', 'tvaRate'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("facture-{$facture->numero_facture}.pdf");
    }

    private function checkOwnership(Facture $facture): void
    {
        $etabId = $facture->reservation?->etablissement_id
            ?? $facture->commandeRestaurant?->etablissement_id;

        if ($etabId !== $this->etabId()) abort(403);
    }
}

