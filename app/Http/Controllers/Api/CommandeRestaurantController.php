<?php

namespace App\Http\Controllers\Api;

use App\Models\ArticleMenu;
use App\Models\CommandeRestaurant;
use App\Models\Facture;
use App\Models\LigneCommande;
use App\Models\LigneFacture;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommandeRestaurantController extends BaseController
{
    public function index(Request $request)
    {
        $query = CommandeRestaurant::where('etablissement_id', $this->etabId())
            ->with(['client', 'menus', 'ligneCommandes.article']);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('type_commande')) {
            $query->where('type_commande', $request->type_commande);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        return $this->success($query->latest()->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id'      => 'nullable|exists:clients,id',
            'reservation_id' => 'nullable|exists:reservations,id',
            'type_commande'  => 'required|in:sur_place,room_service,emporter',
            'numero_table'   => 'nullable|integer|min:1',
            'numero_chambre' => 'nullable|string|max:10',
            'menus'          => 'nullable|array',
            'menus.*'        => 'exists:menus,id',
            'lignes'         => 'required|array|min:1',
            'lignes.*.article_id' => 'required|exists:article_menus,id',
            'lignes.*.quantite'   => 'required|integer|min:1',
            'lignes.*.notes'      => 'nullable|string',
        ]);

        $menuIds = $data['menus'] ?? [];

        if (!empty($menuIds)) {
            $invalid = Menu::whereIn('id', $menuIds)
                ->where('etablissement_id', '!=', $this->etabId())
                ->exists();
            if ($invalid) abort(403, 'Un ou plusieurs menus n\'appartiennent pas a cet etablissement.');
        }

        $articleIds = collect($data['lignes'])->pluck('article_id')->unique();

        if (!empty($menuIds)) {
            $horsMenus = ArticleMenu::whereIn('id', $articleIds)
                ->whereDoesntHave('menus', fn($q) => $q->whereIn('menus.id', $menuIds))
                ->pluck('nom');

            if ($horsMenus->isNotEmpty()) {
                return $this->error(
                    'Ces articles ne font partie d\'aucun menu selectionne : ' . $horsMenus->implode(', '),
                    422
                );
            }
        } else {
            $horsEtab = ArticleMenu::whereIn('id', $articleIds)
                ->where('etablissement_id', '!=', $this->etabId())
                ->exists();
            if ($horsEtab) abort(403);
        }

        $lignes = collect($data['lignes'])->map(function ($ligne) {
            $article = ArticleMenu::findOrFail($ligne['article_id']);
            return array_merge($ligne, ['prix_unitaire' => $article->prix]);
        });

        $montantTotal = $lignes->sum(fn($l) => $l['quantite'] * $l['prix_unitaire']);

        $commande = CommandeRestaurant::create([
            'etablissement_id' => $this->etabId(),
            'client_id'        => $data['client_id'] ?? null,
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

            $article = ArticleMenu::find($ligne['article_id']);
            $article->stock?->decrement('quantite_disponible', $ligne['quantite']);
        }

        if (!empty($menuIds)) {
            $commande->menus()->attach($menuIds);
        }

        // Facture brouillon automatique
        $facture = $this->creerFacture($commande, $montantTotal);
        $this->syncLignesFacture($facture, $commande);

        return $this->success(
            $commande->load(['menus', 'ligneCommandes.article', 'client', 'facture']),
            'Commande creee.', 201
        );
    }

    public function show(CommandeRestaurant $commandeRestaurant)
    {
        $this->checkOwnership($commandeRestaurant);
        return $this->success($commandeRestaurant->load(['menus', 'ligneCommandes.article', 'client', 'reservation', 'facture.ligneFactures']));
    }

    public function update(Request $request, CommandeRestaurant $commandeRestaurant)
    {
        $this->checkOwnership($commandeRestaurant);

        $data = $request->validate([
            'statut' => 'required|in:en_attente,en_preparation,servie,annulee',
        ]);

        $commandeRestaurant->update($data);

        // Cascade statut vers les lignes de commande
        $statutLigne = match($data['statut']) {
            'en_preparation' => 'en_preparation',
            'servie'         => 'servi',
            'annulee'        => 'annule',
            default          => 'en_attente',
        };
        $commandeRestaurant->ligneCommandes()->update(['statut' => $statutLigne]);

        // Effets sur la facture
        $facture = $commandeRestaurant->facture;
        if ($facture) {
            if ($data['statut'] === 'servie' && $facture->statut !== 'payee') {
                $facture->update(['statut' => 'payee', 'date_paiement' => now()]);
            }
            if ($data['statut'] === 'annulee' && !in_array($facture->statut, ['payee', 'annulee'])) {
                $facture->update(['statut' => 'annulee']);
            }
        }

        if ($data['statut'] === 'servie') {
            $commandeRestaurant->update(['servie_at' => now()]);
        }

        return $this->success($commandeRestaurant->load(['ligneCommandes.article', 'facture']), 'Commande mise a jour.');
    }

    public function destroy(CommandeRestaurant $commandeRestaurant)
    {
        $this->checkOwnership($commandeRestaurant);

        $commandeRestaurant->ligneCommandes()->update(['statut' => 'annule']);
        $commandeRestaurant->update(['statut' => 'annulee']);

        $facture = $commandeRestaurant->facture;
        if ($facture && !in_array($facture->statut, ['payee', 'annulee'])) {
            $facture->update(['statut' => 'annulee']);
        }

        return $this->success(null, 'Commande annulee.');
    }

    // ─── Gestion des lignes ───────────────────────────────────────────────────

    public function ajouterLigne(Request $request, CommandeRestaurant $commandeRestaurant)
    {
        $this->checkOwnership($commandeRestaurant);

        if ($commandeRestaurant->statut === 'annulee') {
            return $this->error('Impossible de modifier une commande annulee.', 422);
        }

        $data = $request->validate([
            'article_id' => 'required|exists:article_menus,id',
            'quantite'   => 'required|integer|min:1',
            'notes'      => 'nullable|string',
        ]);

        $article = ArticleMenu::where('id', $data['article_id'])
            ->where('etablissement_id', $this->etabId())
            ->firstOrFail();

        $ligne = LigneCommande::create([
            'commande_id'   => $commandeRestaurant->id,
            'article_id'    => $article->id,
            'quantite'      => $data['quantite'],
            'prix_unitaire' => $article->prix,
            'notes'         => $data['notes'] ?? null,
            'statut'        => $commandeRestaurant->statut === 'en_preparation' ? 'en_preparation' : 'en_attente',
        ]);

        $article->stock?->decrement('quantite_disponible', $data['quantite']);

        $this->recalculerTotaux($commandeRestaurant);

        return $this->success($ligne->load('article'), 'Ligne ajoutee.', 201);
    }

    public function modifierLigne(Request $request, CommandeRestaurant $commandeRestaurant, LigneCommande $ligneCommande)
    {
        $this->checkOwnership($commandeRestaurant);

        if ($ligneCommande->commande_id !== $commandeRestaurant->id) abort(404);

        $data = $request->validate([
            'quantite' => 'sometimes|integer|min:1',
            'notes'    => 'nullable|string',
            'statut'   => 'sometimes|in:en_attente,en_preparation,servi,annule',
        ]);

        // Ajustement stock si la quantité change
        if (isset($data['quantite']) && $data['quantite'] !== $ligneCommande->quantite) {
            $diff = $data['quantite'] - $ligneCommande->quantite;
            $article = ArticleMenu::find($ligneCommande->article_id);
            if ($diff > 0) {
                $article->stock?->decrement('quantite_disponible', $diff);
            } else {
                $article->stock?->increment('quantite_disponible', abs($diff));
            }
        }

        $ligneCommande->update($data);
        $this->recalculerTotaux($commandeRestaurant);

        return $this->success($ligneCommande->fresh('article'), 'Ligne mise a jour.');
    }

    public function supprimerLigne(CommandeRestaurant $commandeRestaurant, LigneCommande $ligneCommande)
    {
        $this->checkOwnership($commandeRestaurant);

        if ($ligneCommande->commande_id !== $commandeRestaurant->id) abort(404);

        // Restituer le stock
        $article = ArticleMenu::find($ligneCommande->article_id);
        $article->stock?->increment('quantite_disponible', $ligneCommande->quantite);

        $ligneCommande->delete();
        $this->recalculerTotaux($commandeRestaurant);

        return $this->success(null, 'Ligne supprimee.');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function creerFacture(CommandeRestaurant $commande, float $montantHt): Facture
    {
        ['tva' => $tva, 'montantTtc' => $montantTtc] = $this->calculerTva($montantHt);

        return Facture::create([
            'commande_restaurant_id' => $commande->id,
            'reservation_id'         => $commande->reservation_id,
            'client_id'              => $commande->client_id,
            'numero_facture'         => 'FAC-' . date('Ymd') . '-' . str_pad(Facture::count() + 1, 4, '0', STR_PAD_LEFT),
            'type_facture'           => 'restaurant',
            'statut'                 => 'brouillon',
            'montant_ht'             => $montantHt,
            'tva'                    => $tva,
            'montant_ttc'            => $montantTtc,
            'date_emission'          => now(),
        ]);
    }

    private function syncLignesFacture(Facture $facture, CommandeRestaurant $commande): void
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

    private function recalculerTotaux(CommandeRestaurant $commande): void
    {
        $montantHt = (float) $commande->ligneCommandes()
            ->where('statut', '!=', 'annule')
            ->sum(DB::raw('quantite * prix_unitaire'));

        $commande->update(['montant_total' => $montantHt]);

        $facture = $commande->facture;
        if ($facture && $facture->statut !== 'payee') {
            ['tva' => $tva, 'montantTtc' => $montantTtc] = $this->calculerTva($montantHt);
            $facture->update([
                'montant_ht'  => $montantHt,
                'tva'         => $tva,
                'montant_ttc' => $montantTtc,
            ]);
            $this->syncLignesFacture($facture, $commande);
        }
    }

    private function checkOwnership(CommandeRestaurant $commandeRestaurant): void
    {
        if ($commandeRestaurant->etablissement_id !== $this->etabId()) abort(403);
    }
}
