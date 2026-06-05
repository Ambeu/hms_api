<?php

namespace App\Http\Controllers\Api;

use App\Models\ObjetOublie;
use Illuminate\Http\Request;

class ObjetOublieController extends BaseController
{
    public function index(Request $request)
    {
        $query = ObjetOublie::whereHas('chambre', fn($q) => $q->where('etablissement_id', $this->etabId()))
            ->with(['chambre.etage', 'client']);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        return $this->success($query->latest('date_signalement')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'chambre_id'  => 'required|exists:chambres,id',
            'client_id'   => 'nullable|exists:clients,id',
            'description' => 'required|string',
        ]);

        $data['statut']           = 'signale';
        $data['date_signalement'] = now();

        return $this->success(
            ObjetOublie::create($data)->load(['chambre', 'client']),
            'Objet signalé.', 201
        );
    }

    public function show(ObjetOublie $objetOublie)
    {
        $this->checkOwnership($objetOublie);
        return $this->success($objetOublie->load(['chambre', 'client']));
    }

    public function update(Request $request, ObjetOublie $objetOublie)
    {
        $this->checkOwnership($objetOublie);

        $data = $request->validate([
            'statut'      => 'sometimes|in:signale,conserve,rendu,detruit',
            'client_id'   => 'nullable|exists:clients,id',
            'description' => 'sometimes|string',
        ]);

        $objetOublie->update($data);
        return $this->success($objetOublie->fresh(['chambre', 'client']), 'Objet mis à jour.');
    }

    public function destroy(ObjetOublie $objetOublie)
    {
        $this->checkOwnership($objetOublie);
        $objetOublie->delete();
        return $this->success(null, 'Enregistrement supprimé.');
    }

    private function checkOwnership(ObjetOublie $objetOublie): void
    {
        if ($objetOublie->chambre->etablissement_id !== $this->etabId()) abort(403);
    }
}

