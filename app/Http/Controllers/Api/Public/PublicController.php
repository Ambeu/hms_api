<?php

namespace App\Http\Controllers\Api\Public;

use App\Models\ArticleMenu;
use App\Models\AvisClient;
use App\Models\Chambre;
use App\Models\Etablissement;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;

class PublicController extends Controller
{
    private function etab(string $slug): Etablissement
    {
        return Etablissement::where('slug', $slug)->firstOrFail();
    }

    public function info(string $slug)
    {
        $etab = $this->etab($slug);
        return response()->json([
            'success' => true,
            'data'    => $etab->load(['typeEtablissement', 'devise']),
        ]);
    }

    public function stats(string $slug)
    {
        $etab = $this->etab($slug);
        $etabId = $etab->id;

        $nbChambres = Chambre::where('etablissement_id', $etabId)->count();

        // Avis : soit rattachés directement à l'établissement,
        // soit via une réservation de cet établissement
        $avisQuery = AvisClient::where(function ($q) use ($etabId) {
            $q->where('etablissement_id', $etabId)
              ->orWhereHas('reservation', fn($r) => $r->where('etablissement_id', $etabId));
        });

        $nbAvis      = $avisQuery->count();
        $noteMoyenne = $nbAvis ? round($avisQuery->avg('note_globale'), 1) : null;

        // Clients : soit enregistrés sur le portail de cet établissement,
        // soit ayant une réservation ici
        $nbClients = \App\Models\Client::where(function ($q) use ($etabId) {
            $q->where('etablissement_id', $etabId)
              ->orWhereHas('reservations', fn($r) => $r->where('etablissement_id', $etabId));
        })->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'nb_chambres'  => $nbChambres,
                'note_moyenne' => $noteMoyenne,
                'nb_avis'      => $nbAvis,
                'nb_clients'   => $nbClients,
            ],
        ]);
    }

    public function chambres(Request $request, string $slug)
    {
        $etab = $this->etab($slug);

        $query = Chambre::where('etablissement_id', $etab->id)
            ->where('statut', 'disponible')
            ->with(['typeChambre', 'etage', 'images']);

        if ($request->filled('capacite')) {
            $query->whereHas('typeChambre', fn($q) => $q->where('capacite_max', '>=', $request->capacite));
        }

        if ($request->filled('type')) {
            $query->whereHas('typeChambre', fn($q) => $q->where('nom', $request->type));
        }

        if ($request->filled('max_prix')) {
            $query->whereHas('typeChambre', fn($q) => $q->where('prix_base', '<=', $request->max_prix));
        }

        if ($request->filled('date_arrivee') && $request->filled('date_depart')) {
            $arrivee = Carbon::parse($request->date_arrivee);
            $depart  = Carbon::parse($request->date_depart);

            $query->whereDoesntHave('reservations', function ($q) use ($arrivee, $depart) {
                $q->whereIn('statut', ['confirmee', 'en_attente', 'en_cours'])
                  ->where('date_arrivee', '<', $depart)
                  ->where('date_depart', '>', $arrivee);
            });
        }

        $chambres = $query->orderBy('numero')->get()->map(fn($c) => $this->formatChambre($c));

        return response()->json(['success' => true, 'data' => $chambres]);
    }

    public function menus(string $slug)
    {
        $etab  = $this->etab($slug);
        $menus = $etab->menus()
            ->where('actif', true)
            ->with(['images', 'articleMenus' => fn($q) => $q->where('disponible', true)->with(['categorie'])])
            ->get();

        return response()->json(['success' => true, 'data' => $menus]);
    }

    public function articles(Request $request, string $slug)
    {
        $etab = $this->etab($slug);

        $query = ArticleMenu::where('etablissement_id', $etab->id)
            ->where('disponible', true)
            ->with('categorie');

        if ($request->filled('categorie_id')) {
            $query->where('categorie_id', $request->categorie_id);
        }

        if ($request->filled('type_repas')) {
            $query->whereHas('menus', fn($q) => $q->where('type_repas', $request->type_repas)->where('actif', true));
        }

        return response()->json([
            'success' => true,
            'data'    => $query->orderBy('nom')->get()->map(fn($a) => [
                'id'          => $a->id,
                'nom'         => $a->nom,
                'desc'        => $a->description,
                'description' => $a->description,
                'prix'        => $a->prix,
                'disponible'  => $a->disponible,
                'allergenes'  => $a->allergenes ?? [],
                'image_url'   => $a->image_url ?? null,
                'cat'         => $a->categorie_id,
                'categorie'   => $a->categorie,
            ]),
        ]);
    }

    public function avis(string $slug)
    {
        $etab = $this->etab($slug);

        $avis = AvisClient::where(function ($q) use ($etab) {
                $q->where('etablissement_id', $etab->id)
                  ->orWhereHas('reservation', fn($r) => $r->where('etablissement_id', $etab->id));
            })
            ->with(['client:id,prenom,nom', 'reservation.chambre.typeChambre'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn($a) => [
                'id'          => $a->id,
                'note'        => $a->note_globale,
                'note_globale'=> $a->note_globale,
                'note_chambre'=> $a->note_chambre,
                'note_service'=> $a->note_service,
                'commentaire' => $a->commentaire,
                'prenom'      => $a->client?->prenom ?? 'Client',
                'nom'         => $a->client?->nom ?? '',
                'chambre'     => $a->reservation?->chambre?->typeChambre?->nom ?? 'Séjour HMS',
                'created_at'  => $a->created_at,
            ]);

        return response()->json(['success' => true, 'data' => $avis]);
    }

    public function tarifs(Request $request, string $slug)
    {
        $etab = $this->etab($slug);

        $query = \App\Models\Tarif::whereHas('typeChambre', fn($q) => $q->where('etablissement_id', $etab->id))
            ->with(['typeChambre'])
            ->where(function ($q) {
                $today = now()->toDateString();
                $q->whereNull('date_debut')->orWhere('date_debut', '<=', $today);
            })
            ->where(function ($q) {
                $today = now()->toDateString();
                $q->whereNull('date_fin')->orWhere('date_fin', '>=', $today);
            });

        if ($request->filled('unite')) {
            $query->where('unite', $request->unite);
        }

        if ($request->filled('type_tarif')) {
            $query->where('type_tarif', $request->type_tarif);
        }

        // Filtrer les tarifs compatibles avec une chambre spécifique
        if ($request->filled('chambre_id')) {
            $typeChambreId = Chambre::where('id', $request->chambre_id)
                ->where('etablissement_id', $etab->id)
                ->value('type_chambre_id');

            if ($typeChambreId) {
                $query->where('type_chambre_id', $typeChambreId);
            }
        }

        $tarifs = $query->orderBy('prix')->get()->map(fn($t) => [
            'id'             => $t->id,
            'nom'            => $t->nom,
            'prix'           => (float) $t->prix,
            'unite'          => $t->unite,
            'duree_min'      => $t->duree_min,
            'type_tarif'     => $t->type_tarif,
            'date_debut'     => $t->date_debut,
            'date_fin'       => $t->date_fin,
            'type_chambre'   => [
                'id'          => $t->typeChambre->id,
                'nom'         => $t->typeChambre->nom,
                'capacite'    => $t->typeChambre->capacite_max,
                'superficie'  => $t->typeChambre->superficie_m2,
                'prix_base'   => (float) $t->typeChambre->prix_base,
            ],
        ]);

        return response()->json(['success' => true, 'data' => $tarifs]);
    }

    // ─── Format chambre pour le frontend public ───────────────────────────────

    private function formatChambre(Chambre $c): array
    {
        $type = $c->typeChambre;
        return [
            'id'          => $c->id,
            'numero'      => $c->numero,
            'type'        => $type?->nom,
            'etage'       => $c->etage?->numero,
            'vue'         => $c->vue,
            'description' => $c->description ?? $type?->description,
            'capacite'    => $type?->capacite_max,
            'superficie'  => $type?->superficie_m2,
            'prix'        => (float) ($type?->prix_base ?? 0),
            'disponible'  => $c->statut === 'disponible',
            'equipements' => $c->equipements ?? [],
            'images'      => $c->images,
            'image_principale' => $c->images->firstWhere('principale', true) ?? $c->images->first(),
            // Champs bruts pour le back-office
            'statut'           => $c->statut,
            'type_chambre_id'  => $c->type_chambre_id,
            'etage_id'         => $c->etage_id,
            'typeChambre'      => $type,
            'etageObj'         => $c->etage,
        ];
    }
}
