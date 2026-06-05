<?php

namespace App\Http\Controllers\Api;

use App\Models\TypeChambre;
use Illuminate\Http\Request;

class TypeChambreController extends BaseController
{
    public function index()
    {
        return $this->success(
            TypeChambre::where('etablissement_id', $this->etabId())
                ->orderBy('nom')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom'          => 'required|string|max:100',
            'description'  => 'nullable|string',
            'prix_base'    => 'required|numeric|min:0',
            'capacite_max' => 'required|integer|min:1',
            'nb_lits'      => 'required|integer|min:1',
            'superficie_m2'=> 'nullable|string|max:20',
        ]);

        $data['etablissement_id'] = $this->etabId();
        return $this->success(TypeChambre::create($data), 'Type de chambre créé.', 201);
    }

    public function show(TypeChambre $typeChambre)
    {
        $this->checkOwnership($typeChambre);
        return $this->success($typeChambre->load('tarifs'));
    }

    public function update(Request $request, TypeChambre $typeChambre)
    {
        $this->checkOwnership($typeChambre);

        $data = $request->validate([
            'nom'          => 'sometimes|string|max:100',
            'description'  => 'nullable|string',
            'prix_base'    => 'sometimes|numeric|min:0',
            'capacite_max' => 'sometimes|integer|min:1',
            'nb_lits'      => 'sometimes|integer|min:1',
            'superficie_m2'=> 'nullable|string|max:20',
        ]);

        $typeChambre->update($data);
        return $this->success($typeChambre, 'Type de chambre mis à jour.');
    }

    public function destroy(TypeChambre $typeChambre)
    {
        $this->checkOwnership($typeChambre);
        $typeChambre->delete();
        return $this->success(null, 'Type de chambre supprimé.');
    }

    private function checkOwnership(TypeChambre $typeChambre): void
    {
        if ($typeChambre->etablissement_id !== $this->etabId()) abort(403);
    }
}

