<?php

namespace App\Http\Controllers\Api;

use App\Models\Chambre;
use App\Models\ChambreImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChambreController extends BaseController
{
    public function index(Request $request)
    {
        $query = Chambre::where('etablissement_id', $this->etabId())
            ->with(['typeChambre', 'etage', 'images']);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('etage_id')) {
            $query->where('etage_id', $request->etage_id);
        }

        if ($request->filled('type_chambre_id')) {
            $query->where('type_chambre_id', $request->type_chambre_id);
        }

        return $this->success($query->orderBy('numero')->get());
    }

    public function reservees(Request $request)
    {
        $request->validate([
            'date_arrivee' => 'nullable|date',
            'date_depart'  => 'nullable|date|after_or_equal:date_arrivee',
            'statut'       => 'nullable|in:en_attente,confirmee,en_cours',
        ]);

        $statutsActifs = $request->filled('statut')
            ? [$request->statut]
            : ['en_attente', 'confirmee', 'en_cours'];

        $query = Chambre::where('etablissement_id', $this->etabId())
            ->with(['typeChambre', 'etage', 'images'])
            ->whereHas('reservations', function ($q) use ($request, $statutsActifs) {
                $q->whereIn('statut', $statutsActifs);

                if ($request->filled('date_arrivee') && $request->filled('date_depart')) {
                    $q->where('date_arrivee', '<', $request->date_depart)
                      ->where('date_depart', '>', $request->date_arrivee);
                } elseif ($request->filled('date_arrivee')) {
                    $q->whereDate('date_arrivee', '>=', $request->date_arrivee);
                }
            })
            ->with(['reservations' => function ($q) use ($statutsActifs) {
                $q->whereIn('statut', $statutsActifs)
                  ->with(['client', 'tarif'])
                  ->orderBy('date_arrivee');
            }]);

        return $this->success($query->orderBy('numero')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type_chambre_id' => 'required|exists:type_chambres,id',
            'etage_id'        => 'required|exists:etages,id',
            'numero'          => 'required|string|max:10',
            'statut'          => 'nullable|in:disponible,occupee,en_nettoyage,hors_service',
            'vue'             => 'nullable|string|max:100',
            'hors_service'    => 'boolean',
            'equipements'     => 'nullable|array',
            'description'     => 'nullable|string',
            'images'          => 'nullable|array|max:10',
            'images.*'        => 'file|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $data['etablissement_id'] = $this->etabId();
        $chambre = Chambre::create($data);

        if ($request->hasFile('images')) {
            $this->sauvegarderImages($request->file('images'), $chambre);
        }

        return $this->success(
            $chambre->load(['typeChambre', 'etage', 'images']),
            'Chambre créée.', 201
        );
    }

    public function show(Chambre $chambre)
    {
        $this->checkOwnership($chambre);
        return $this->success($chambre->load(['typeChambre', 'etage', 'images', 'reservations' => fn($q) => $q->where('statut', 'confirmee')]));
    }

    public function update(Request $request, Chambre $chambre)
    {
        $this->checkOwnership($chambre);

        $data = $request->validate([
            'type_chambre_id' => 'sometimes|exists:type_chambres,id',
            'etage_id'        => 'sometimes|exists:etages,id',
            'numero'          => 'sometimes|string|max:10',
            'statut'          => 'nullable|in:disponible,occupee,en_nettoyage,hors_service',
            'vue'             => 'nullable|string|max:100',
            'hors_service'    => 'boolean',
            'equipements'     => 'nullable|array',
            'description'     => 'nullable|string',
            'images'          => 'nullable|array|max:10',
            'images.*'        => 'file|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $chambre->update($data);

        if ($request->hasFile('images')) {
            $this->sauvegarderImages($request->file('images'), $chambre);
        }

        return $this->success($chambre->fresh(['typeChambre', 'etage', 'images']), 'Chambre mise à jour.');
    }

    public function destroy(Chambre $chambre)
    {
        $this->checkOwnership($chambre);
        $chambre->delete();
        return $this->success(null, 'Chambre supprimée.');
    }

    // ─── Images ───────────────────────────────────────────────────────────────

    public function ajouterImages(Request $request, Chambre $chambre)
    {
        $this->checkOwnership($chambre);

        $request->validate([
            'images'          => 'required|array|min:1|max:10',
            'images.*'        => 'required|file|mimes:jpeg,jpg,png,webp|max:5120',
            'principale'      => 'nullable|integer|min:0',
        ]);

        $offsetOrdre = $chambre->images()->count();
        $creees      = [];

        foreach ($request->file('images') as $index => $file) {
            $path = $file->store("chambres/{$chambre->id}", 'public');
            $url  = \Illuminate\Support\Facades\Storage::url($path);

            $estPrincipale = ((int) $request->input('principale', 0)) === $index
                && $chambre->images()->where('principale', true)->doesntExist();

            if ($estPrincipale) {
                $chambre->images()->update(['principale' => false]);
            }

            $creees[] = ChambreImage::create([
                'chambre_id' => $chambre->id,
                'url'        => $url,
                'ordre'      => $offsetOrdre + $index,
                'principale' => $estPrincipale,
            ]);
        }

        return $this->success($chambre->fresh('images'), 'Images téléversées.', 201);
    }

    public function supprimerImage(Chambre $chambre, ChambreImage $chambreImage)
    {
        $this->checkOwnership($chambre);
        if ($chambreImage->chambre_id !== $chambre->id) abort(404);

        // Supprimer le fichier physique
        $path = ltrim(parse_url($chambreImage->url, PHP_URL_PATH), '/');
        $path = str_replace('storage/', '', $path);
        \Illuminate\Support\Facades\Storage::disk('public')->delete($path);

        $chambreImage->delete();
        return $this->success(null, 'Image supprimée.');
    }

    public function definirPrincipale(Chambre $chambre, ChambreImage $chambreImage)
    {
        $this->checkOwnership($chambre);
        if ($chambreImage->chambre_id !== $chambre->id) abort(404);

        $chambre->images()->update(['principale' => false]);
        $chambreImage->update(['principale' => true]);

        return $this->success($chambre->fresh('images'), 'Image principale définie.');
    }

    private function sauvegarderImages(array $files, Chambre $chambre): void
    {
        $offset = $chambre->images()->count();
        $sansPrincipale = $chambre->images()->where('principale', true)->doesntExist();

        foreach ($files as $index => $file) {
            $url = Storage::url($file->store("chambres/{$chambre->id}", 'public'));
            ChambreImage::create([
                'chambre_id' => $chambre->id,
                'url'        => $url,
                'ordre'      => $offset + $index,
                'principale' => $sansPrincipale && $index === 0,
            ]);
        }
    }

    private function checkOwnership(Chambre $chambre): void
    {
        if ($chambre->etablissement_id !== $this->etabId()) abort(403);
    }
}

