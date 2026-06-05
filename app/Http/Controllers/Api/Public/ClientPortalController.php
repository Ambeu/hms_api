<?php

namespace App\Http\Controllers\Api\Public;

use App\Models\ArticleMenu;
use App\Models\AvisClient;
use App\Models\Chambre;
use App\Models\CommandeRestaurant;
use App\Models\Etablissement;
use App\Models\Facture;
use App\Models\LigneCommande;
use App\Models\LigneFacture;
use App\Models\Menu;
use App\Models\ObjetOublie;
use App\Models\Reservation;
use App\Models\Tarif;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ClientPortalController extends Controller
{
    private function client()
    {
        return auth('client')->user();
    }

    private function etabId(): int
    {
        return $this->client()->etablissement_id;
    }

    // ─── Réservations ────────────────────────────────────────────────────────

    public function mesReservations()
    {
        $reservations = Reservation::where('client_id', $this->client()->id)
            ->where('etablissement_id', $this->etabId())
            ->with(['chambre.typeChambre', 'tarif', 'factures'])
            ->orderByDesc('date_arrivee')
            ->get();

        return response()->json(['success' => true, 'data' => $reservations]);
    }

    public function reserver(Request $request)
    {
        $data = $request->validate([
            'chambre_id'      => 'required|exists:chambres,id',
            'tarif_id'        => 'nullable|exists:tarifs,id',
            'date_arrivee'    => 'required|date|after_or_equal:today',
            'date_depart'     => 'required|date|after:date_arrivee',
            'nb_adultes'      => 'required|integer|min:1',
            'nb_enfants'      => 'nullable|integer|min:0',
            'notes_speciales' => 'nullable|string',
        ]);

        $chambre = Chambre::where('id', $data['chambre_id'])
            ->where('etablissement_id', $this->etabId())
            ->firstOrFail();

        $conflit = Reservation::where('chambre_id', $data['chambre_id'])
            ->whereIn('statut', ['confirmee', 'en_attente', 'en_cours'])
            ->where('date_arrivee', '<', $data['date_depart'])
            ->where('date_depart', '>', $data['date_arrivee'])
            ->exists();

        if ($conflit) {
            return response()->json(['success' => false, 'message' => 'Chambre non disponible sur cette periode.'], 422);
        }

        $data['date_arrivee'] = Carbon::parse($data['date_arrivee'])->format('Y-m-d H:i:s');
        $data['date_depart']  = Carbon::parse($data['date_depart'])->format('Y-m-d H:i:s');

        $prixUnitaire = 0;
        $nbUnites     = 1;

        if (!empty($data['tarif_id'])) {
            $tarif  = Tarif::findOrFail($data['tarif_id']);
            $debut  = Carbon::parse($data['date_arrivee']);
            $fin    = Carbon::parse($data['date_depart']);

            $nbUnites = match ($tarif->unite) {
                'heure'   => max(1, $debut->diffInHours($fin)),
                'jour'    => max(1, $debut->diffInDays($fin)),
                'semaine' => max(1, (int) ceil($debut->diffInDays($fin) / 7)),
                default   => max(1, $debut->diffInDays($fin)),
            };

            if ($nbUnites < $tarif->duree_min) {
                return response()->json([
                    'success' => false,
                    'message' => "Ce tarif exige un minimum de {$tarif->duree_min} {$tarif->unite}(s).",
                ], 422);
            }

            $prixUnitaire       = $tarif->prix;
            $data['unite']      = $tarif->unite;
            $data['prix_total'] = $tarif->prix * $nbUnites;
        }

        $reservation = Reservation::create([
            'etablissement_id'  => $this->etabId(),
            'client_id'         => $this->client()->id,
            'chambre_id'        => $data['chambre_id'],
            'tarif_id'          => $data['tarif_id'] ?? null,
            'date_arrivee'      => $data['date_arrivee'],
            'date_depart'       => $data['date_depart'],
            'nb_adultes'        => $data['nb_adultes'],
            'nb_enfants'        => $data['nb_enfants'] ?? 0,
            'notes_speciales'   => $data['notes_speciales'] ?? null,
            'unite'             => $data['unite'] ?? 'nuit',
            'prix_total'        => $data['prix_total'] ?? 0,
            'code_confirmation' => strtoupper(Str::random(8)),
            'statut'            => 'en_attente',
        ]);

        // Facture brouillon automatique
        $montantHt  = (float) ($data['prix_total'] ?? 0);
        $tauxTva    = (float) (Etablissement::find($this->etabId())?->taux_tva ?? 18) / 100;
        $tva        = round($montantHt * $tauxTva, 2);
        $montantTtc = $montantHt + $tva;

        $facture = Facture::create([
            'reservation_id' => $reservation->id,
            'client_id'      => $this->client()->id,
            'numero_facture' => 'FAC-' . date('Ymd') . '-' . str_pad(Facture::count() + 1, 4, '0', STR_PAD_LEFT),
            'type_facture'   => 'sejour',
            'statut'         => 'brouillon',
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

        return response()->json([
            'success' => true,
            'message' => 'Demande de reservation enregistree.',
            'data'    => $reservation->load(['chambre.typeChambre', 'tarif', 'factures']),
        ], 201);
    }

    public function annulerReservation(Reservation $reservation)
    {
        if ($reservation->client_id !== $this->client()->id) abort(403);
        if (!in_array($reservation->statut, ['en_attente', 'confirmee'])) {
            return response()->json(['success' => false, 'message' => 'Cette reservation ne peut plus etre annulee.'], 422);
        }

        $reservation->update(['statut' => 'annulee']);
        $reservation->chambre->update(['statut' => 'disponible']);
        $reservation->factures()->whereNotIn('statut', ['payee', 'annulee'])->update(['statut' => 'annulee']);

        return response()->json(['success' => true, 'message' => 'Reservation annulee.']);
    }

    // ─── Commandes restaurant ─────────────────────────────────────────────────

    public function mesCommandes()
    {
        $commandes = CommandeRestaurant::where('client_id', $this->client()->id)
            ->where('etablissement_id', $this->etabId())
            ->with(['ligneCommandes.article', 'menus'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['success' => true, 'data' => $commandes]);
    }

    public function commander(Request $request)
    {
        $data = $request->validate([
            'type_commande'       => 'required|in:sur_place,room_service,emporter',
            'numero_table'        => 'nullable|integer|min:1',
            'numero_chambre'      => 'nullable|string|max:10',
            'reservation_id'      => 'nullable|exists:reservations,id',
            'menus'               => 'nullable|array',
            'menus.*'             => 'exists:menus,id',
            'lignes'              => 'required|array|min:1',
            'lignes.*.article_id' => 'required|exists:article_menus,id',
            'lignes.*.quantite'   => 'required|integer|min:1',
            'lignes.*.notes'      => 'nullable|string',
        ]);

        $menuIds    = $data['menus'] ?? [];
        $articleIds = collect($data['lignes'])->pluck('article_id')->unique();

        // Vérifier que les menus appartiennent à l'établissement
        if (!empty($menuIds)) {
            $invalid = Menu::whereIn('id', $menuIds)
                ->where('etablissement_id', '!=', $this->etabId())
                ->exists();
            if ($invalid) {
                return response()->json(['success' => false, 'message' => 'Menus invalides pour cet etablissement.'], 403);
            }

            // Vérifier que les articles appartiennent aux menus sélectionnés
            $horsMenus = ArticleMenu::whereIn('id', $articleIds)
                ->whereDoesntHave('menus', fn($q) => $q->whereIn('menus.id', $menuIds))
                ->pluck('nom');

            if ($horsMenus->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ces articles ne font partie d\'aucun menu selectionne : ' . $horsMenus->implode(', '),
                ], 422);
            }
        } else {
            $horsEtab = ArticleMenu::whereIn('id', $articleIds)
                ->where('etablissement_id', '!=', $this->etabId())
                ->exists();
            if ($horsEtab) abort(403);
        }

        $lignes = collect($data['lignes'])->map(function ($ligne) {
            $article = ArticleMenu::findOrFail($ligne['article_id']);
            return array_merge($ligne, ['prix_unitaire' => $article->prix, 'article' => $article]);
        });

        $montantTotal = $lignes->sum(fn($l) => $l['quantite'] * $l['prix_unitaire']);

        $commande = CommandeRestaurant::create([
            'etablissement_id' => $this->etabId(),
            'client_id'        => $this->client()->id,
            'reservation_id'   => $data['reservation_id'] ?? null,
            'type_commande'    => $data['type_commande'],
            'statut'           => 'en_attente',
            'numero_table'     => $data['numero_table'] ?? null,
            'numero_chambre'   => $data['numero_chambre'] ?? null,
            'montant_total'    => $montantTotal,
        ]);

        foreach ($lignes as $ligne) {
            LigneCommande::create([
                'commande_id'   => $commande->id,
                'article_id'    => $ligne['article_id'],
                'quantite'      => $ligne['quantite'],
                'prix_unitaire' => $ligne['prix_unitaire'],
                'notes'         => $ligne['notes'] ?? null,
                'statut'        => 'en_attente',
            ]);
            $ligne['article']->stock?->decrement('quantite_disponible', $ligne['quantite']);
        }

        if (!empty($menuIds)) {
            $commande->menus()->attach($menuIds);
        }

        // Facture brouillon automatique
        $facture = $this->creerFactureCommande($commande, $montantTotal);
        $this->syncLignesFactureCommande($facture, $commande);

        return response()->json([
            'success' => true,
            'message' => 'Commande passee avec succes.',
            'data'    => $commande->load(['menus', 'ligneCommandes.article', 'facture']),
        ], 201);
    }

    private function creerFactureCommande(CommandeRestaurant $commande, float $montantHt): Facture
    {
        $tauxTva    = (float) (Etablissement::find($this->etabId())?->taux_tva ?? 18) / 100;
        $tva        = round($montantHt * $tauxTva, 2);

        return Facture::create([
            'commande_restaurant_id' => $commande->id,
            'client_id'              => $commande->client_id,
            'numero_facture'         => 'FAC-' . date('Ymd') . '-' . str_pad(Facture::count() + 1, 4, '0', STR_PAD_LEFT),
            'type_facture'           => 'restaurant',
            'statut'                 => 'brouillon',
            'montant_ht'             => $montantHt,
            'tva'                    => $tva,
            'montant_ttc'            => $montantHt + $tva,
            'date_emission'          => now(),
        ]);
    }

    private function syncLignesFactureCommande(Facture $facture, CommandeRestaurant $commande): void
    {
        $facture->ligneFactures()->delete();
        foreach ($commande->ligneCommandes()->where('statut', '!=', 'annule')->with('article')->get() as $ligne) {
            LigneFacture::create([
                'facture_id'        => $facture->id,
                'description'       => $ligne->article->nom,
                'categorie'         => 'restaurant',
                'quantite'          => $ligne->quantite,
                'prix_unitaire'     => $ligne->prix_unitaire,
                'montant_total'     => $ligne->quantite * $ligne->prix_unitaire,
                'date_consommation' => now(),
            ]);
        }
    }

    // ─── Avis ────────────────────────────────────────────────────────────────

    public function mesAvis()
    {
        $avis = AvisClient::where('client_id', $this->client()->id)
            ->where('etablissement_id', $this->etabId())
            ->with('reservation.chambre.typeChambre')
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $avis]);
    }

    public function laisserAvis(Request $request)
    {
        $data = $request->validate([
            'note_globale'      => 'required|integer|min:1|max:5',
            'note_chambre'      => 'nullable|integer|min:1|max:5',
            'note_service'      => 'nullable|integer|min:1|max:5',
            'note_restauration' => 'nullable|integer|min:1|max:5',
            'commentaire'       => 'nullable|string|max:2000',
            'reservation_id'    => 'nullable|exists:reservations,id',
        ]);

        if (!empty($data['reservation_id'])) {
            $res = Reservation::find($data['reservation_id']);
            if (!$res || $res->client_id !== $this->client()->id) abort(403);
        }

        $avis = AvisClient::updateOrCreate(
            ['client_id' => $this->client()->id, 'reservation_id' => $data['reservation_id'] ?? null],
            array_merge($data, ['etablissement_id' => $this->etabId()])
        );

        return response()->json(['success' => true, 'message' => 'Avis enregistre.', 'data' => $avis], 201);
    }

    // ─── Objets perdus ────────────────────────────────────────────────────────

    public function mesObjets()
    {
        $objets = ObjetOublie::where('client_id', $this->client()->id)
            ->where('etablissement_id', $this->etabId())
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $objets]);
    }

    public function signalerObjet(Request $request)
    {
        $data = $request->validate([
            'description'        => 'required|string|max:500',
            'numero_chambre'     => 'nullable|string|max:10',
            'date_sejour'        => 'nullable|date',
            'contact_restitution'=> 'required|string|max:150',
        ]);

        $chambreId = null;
        if (!empty($data['numero_chambre'])) {
            $chambreId = Chambre::where('etablissement_id', $this->etabId())
                ->where('numero', $data['numero_chambre'])
                ->value('id');
        }

        $objet = ObjetOublie::create([
            'client_id'           => $this->client()->id,
            'etablissement_id'    => $this->etabId(),
            'chambre_id'          => $chambreId,
            'numero_chambre'      => $data['numero_chambre'] ?? null,
            'description'         => $data['description'],
            'contact_restitution' => $data['contact_restitution'],
            'statut'              => 'signale',
        ]);

        return response()->json(['success' => true, 'message' => 'Signalement enregistre.', 'data' => $objet], 201);
    }
}
