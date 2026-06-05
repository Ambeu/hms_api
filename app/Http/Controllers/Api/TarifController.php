<?php

namespace App\Http\Controllers\Api;

use App\Models\Tarif;
use App\Models\TypeChambre;
use Illuminate\Http\Request;

class TarifController extends BaseController
{
    public function index(Request $request)
    {
        $query = Tarif::whereHas('typeChambre', fn($q) => $q->where('etablissement_id', $this->etabId()))
            ->with('typeChambre');

        if ($request->filled('type_chambre_id')) {
            $query->where('type_chambre_id', $request->type_chambre_id);
        }

        return $this->success($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type_chambre_id' => 'required|exists:type_chambres,id',
            'nom'             => 'required|string|max:100',
            'prix'            => 'required|numeric|min:0',
            'unite'           => 'nullable|in:nuit,heure,jour,semaine',
            'duree_min'       => 'nullable|integer|min:1',
            'date_debut'      => 'nullable|date',
            'date_fin'        => 'nullable|date|after_or_equal:date_debut',
            'type_tarif'      => 'nullable|in:standard,weekend,saison,promo',
        ]);

        // Vérifier que le type de chambre appartient à l'établissement courant
        $typeChambre = TypeChambre::findOrFail($data['type_chambre_id']);
        if ($typeChambre->etablissement_id !== $this->etabId()) abort(403);

        return $this->success(Tarif::create($data)->load('typeChambre'), 'Tarif créé.', 201);
    }

    public function show(Tarif $tarif)
    {
        $this->checkOwnership($tarif);
        return $this->success($tarif->load('typeChambre'));
    }

    public function update(Request $request, Tarif $tarif)
    {
        $this->checkOwnership($tarif);

        $data = $request->validate([
            'nom'       => 'sometimes|string|max:100',
            'prix'      => 'sometimes|numeric|min:0',
            'unite'     => 'nullable|in:nuit,heure,jour,semaine',
            'duree_min' => 'nullable|integer|min:1',
            'date_debut' => 'nullable|date',
            'date_fin'   => 'nullable|date|after_or_equal:date_debut',
            'type_tarif' => 'nullable|in:standard,weekend,saison,promo',
        ]);

        $tarif->update($data);
        return $this->success($tarif->fresh('typeChambre'), 'Tarif mis à jour.');
    }

    public function destroy(Tarif $tarif)
    {
        $this->checkOwnership($tarif);
        $tarif->delete();
        return $this->success(null, 'Tarif supprimé.');
    }

    private function checkOwnership(Tarif $tarif): void
    {
        if ($tarif->typeChambre->etablissement_id !== $this->etabId()) abort(403);
    }
}

