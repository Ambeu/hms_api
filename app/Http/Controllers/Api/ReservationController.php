<?php

namespace App\Http\Controllers\Api;

use App\Models\Chambre;
use App\Models\Facture;
use App\Models\LigneFacture;
use App\Models\Reservation;
use App\Models\TacheMenage;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ReservationController extends BaseController
{
    public function index(Request $request)
    {
        $query = Reservation::where('etablissement_id', $this->etabId())
            ->with(['client', 'chambre.typeChambre', 'tarif']);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('date_arrivee')) {
            $query->whereDate('date_arrivee', $request->date_arrivee);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        return $this->success($query->orderBy('date_arrivee')->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id'       => 'required|exists:clients,id',
            'chambre_id'      => 'required|exists:chambres,id',
            'tarif_id'        => 'nullable|exists:tarifs,id',
            'canal_source'    => 'nullable|string|max:50',
            'date_arrivee'    => 'required|date',
            'date_depart'     => 'required|date',
            'nb_adultes'      => 'required|integer|min:1',
            'nb_enfants'      => 'nullable|integer|min:0',
            'notes_speciales' => 'nullable|string',
        ]);

        $chambre = Chambre::findOrFail($data['chambre_id']);
        if ($chambre->etablissement_id !== $this->etabId()) abort(403);

        // Vérifier disponibilité (fonctionne pour nuits ET heures grâce aux datetime)
        $conflit = Reservation::where('chambre_id', $data['chambre_id'])
            ->whereIn('statut', ['confirmee', 'en_attente'])
            ->where('date_arrivee', '<', $data['date_depart'])
            ->where('date_depart', '>', $data['date_arrivee'])
            ->exists();

        if ($conflit) {
            return $this->error('La chambre est déjà réservée sur cette période.', 422);
        }

        // Parser explicitement pour conserver l'heure (évite la troncature par le cast Eloquent)
        $data['date_arrivee'] = Carbon::parse($data['date_arrivee'])->format('Y-m-d H:i:s');
        $data['date_depart']  = Carbon::parse($data['date_depart'])->format('Y-m-d H:i:s');

        $data['etablissement_id']  = $this->etabId();
        $data['code_confirmation'] = strtoupper(Str::random(8));
        $data['statut']            = 'confirmee';

        // Calcul du prix selon l'unité du tarif
        $prixUnitaire = 0;
        $nbUnites     = 1;

        if (!empty($data['tarif_id'])) {
            $tarif          = \App\Models\Tarif::findOrFail($data['tarif_id']);
            $data['unite']  = $tarif->unite;
            $debut          = Carbon::parse($data['date_arrivee']);
            $fin            = Carbon::parse($data['date_depart']);

            $nbUnites = match ($tarif->unite) {
                'heure'   => max(1, $debut->diffInHours($fin)),
                'jour'    => max(1, $debut->diffInDays($fin)),
                'semaine' => max(1, (int) ceil($debut->diffInDays($fin) / 7)),
                default   => max(1, $debut->diffInDays($fin)),
            };

            if ($nbUnites < $tarif->duree_min) {
                return $this->error(
                    "Ce tarif exige un minimum de {$tarif->duree_min} {$tarif->unite}(s).", 422
                );
            }

            $prixUnitaire       = $tarif->prix;
            $data['prix_total'] = $tarif->prix * $nbUnites;
        }

        $reservation = Reservation::create($data);

        // Génération automatique d'une facture brouillon
        $montantHt            = (float) ($data['prix_total'] ?? 0);
        ['tva' => $tva, 'montantTtc' => $montantTtc] = $this->calculerTva($montantHt);

        $facture = Facture::create([
            'reservation_id' => $reservation->id,
            'client_id'      => $reservation->client_id,
            'numero_facture' => 'FAC-' . date('Ymd') . '-' . str_pad(Facture::count() + 1, 4, '0', STR_PAD_LEFT),
            'type_facture'   => 'sejour',
            'statut'         => 'brouillon',
            'mode_paiement' =>  'especes',
            'montant_ht'     => $montantHt,
            'tva'            => $tva,
            'montant_ttc'    => $montantTtc,
            'date_emission'  => now(),
        ]);

        LigneFacture::create([
            'facture_id'        => $facture->id,
            'description'       => 'Sejour - Chambre ' . $chambre->numero,
            'categorie'         => 'sejour',
            'quantite'          => $nbUnites,
            'prix_unitaire'     => $prixUnitaire,
            'montant_total'     => $montantHt,
            'date_consommation' => now(),
        ]);

        return $this->success(
            $reservation->load(['client', 'chambre.typeChambre', 'tarif', 'factures']),
            'Réservation créée.', 201
        );
    }

    public function show(Reservation $reservation)
    {
        $this->checkOwnership($reservation);
        return $this->success($reservation->load(['client', 'chambre.typeChambre', 'tarif', 'checkinCheckouts', 'factures']));
    }

    public function update(Request $request, Reservation $reservation)
    {
        $this->checkOwnership($reservation);

        $data = $request->validate([
            'statut'          => 'sometimes|in:en_attente,confirmee,annulee,no_show,terminee',
            'date_arrivee'    => 'sometimes|date',
            'date_depart'     => 'sometimes|date|after:date_arrivee',
            'nb_adultes'      => 'sometimes|integer|min:1',
            'nb_enfants'      => 'nullable|integer|min:0',
            'notes_speciales' => 'nullable|string',
            'tarif_id'        => 'nullable|exists:tarifs,id',
            'prix_total'      => 'sometimes|numeric|min:0',
        ]);

        if (isset($data['date_arrivee'])) {
            $data['date_arrivee'] = Carbon::parse($data['date_arrivee'])->format('Y-m-d H:i:s');
        }
        if (isset($data['date_depart'])) {
            $data['date_depart'] = Carbon::parse($data['date_depart'])->format('Y-m-d H:i:s');
        }

        $reservation->update($data);

        if (isset($data['statut'])) {
            $chambreStatut = match($data['statut']) {
                'no_show'            => 'disponible',
                'annulee', 'terminee'=> 'en_nettoyage',
                default              => null,
            };
            if ($chambreStatut) {
                $reservation->chambre->update(['statut' => $chambreStatut]);
            }

            if (in_array($data['statut'], ['terminee', 'annulee'])) {
                TacheMenage::create([
                    'chambre_id'       => $reservation->chambre_id,
                    'agent_id'         => null,
                    'type_tache'       => 'depart',
                    'statut'           => 'non_assignee',
                    'date_assignation' => now(),
                ]);
            }

            if ($data['statut'] === 'terminee') {
                $reservation->factures()
                    ->where('statut', '!=', 'payee')
                    ->update(['statut' => 'payee', 'date_paiement' => now()]);
            }

            if ($data['statut'] === 'annulee') {
                $reservation->factures()
                    ->whereNotIn('statut', ['payee', 'annulee'])
                    ->update(['statut' => 'annulee']);
            }
        }

        return $this->success($reservation->fresh(['client', 'chambre', 'tarif']), 'Réservation mise à jour.');
    }

    public function destroy(Reservation $reservation)
    {
        $this->checkOwnership($reservation);
        $reservation->update(['statut' => 'annulee']);
        $reservation->chambre->update(['statut' => 'en_nettoyage']);

        TacheMenage::create([
            'chambre_id'       => $reservation->chambre_id,
            'agent_id'         => null,
            'type_tache'       => 'depart',
            'statut'           => 'non_assignee',
            'date_assignation' => now(),
        ]);

        $reservation->factures()
            ->whereNotIn('statut', ['payee', 'annulee'])
            ->update(['statut' => 'annulee']);

        return $this->success(null, 'Réservation annulée.');
    }

    private function checkOwnership(Reservation $reservation): void
    {
        if ($reservation->etablissement_id !== $this->etabId()) abort(403);
    }
}

