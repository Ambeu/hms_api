<?php

namespace App\Http\Controllers\Api;

use App\Models\Etage;
use Illuminate\Http\Request;

class EtageController extends BaseController
{
    public function index()
    {
        $etages = Etage::where('etablissement_id', $this->etabId())
            ->orderBy('numero')
            ->withCount('chambres')
            ->get();

        return $this->success($etages);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'numero' => 'required|integer|unique:etages,numero,NULL,id,etablissement_id,' . $this->etabId(),
            'nom'    => 'nullable|string|max:100',
        ]);

        $data['etablissement_id'] = $this->etabId();
        return $this->success(Etage::create($data), 'Étage créé.', 201);
    }

    public function show(Etage $etage)
    {
        $this->checkOwnership($etage);
        return $this->success($etage->load('chambres.typeChambre'));
    }

    public function update(Request $request, Etage $etage)
    {
        $this->checkOwnership($etage);

        $data = $request->validate([
            'numero' => 'sometimes|integer|unique:etages,numero,' . $etage->id . ',id,etablissement_id,' . $this->etabId(),
            'nom'    => 'nullable|string|max:100',
        ]);

        $etage->update($data);
        return $this->success($etage, 'Étage mis à jour.');
    }

    public function destroy(Etage $etage)
    {
        $this->checkOwnership($etage);
        $etage->delete();
        return $this->success(null, 'Étage supprimé.');
    }

    private function checkOwnership(Etage $etage): void
    {
        if ($etage->etablissement_id !== $this->etabId()) {
            abort(403);
        }
    }
}

