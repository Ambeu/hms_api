<?php

namespace App\Http\Controllers\Api;

use App\Models\TypeEtablissement;
use Illuminate\Http\Request;

class TypeEtablissementController extends BaseController
{
    public function index()
    {
        return $this->success(TypeEtablissement::orderBy('nom')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom'         => 'required|string|max:100|unique:type_etablissements,nom',
            'description' => 'nullable|string',
        ]);

        return $this->success(TypeEtablissement::create($data), 'Type cree.', 201);
    }

    public function show(TypeEtablissement $typeEtablissement)
    {
        return $this->success($typeEtablissement);
    }

    public function update(Request $request, TypeEtablissement $typeEtablissement)
    {
        $data = $request->validate([
            'nom'         => 'sometimes|string|max:100|unique:type_etablissements,nom,' . $typeEtablissement->id,
            'description' => 'nullable|string',
        ]);

        $typeEtablissement->update($data);
        return $this->success($typeEtablissement, 'Type mis a jour.');
    }

    public function destroy(TypeEtablissement $typeEtablissement)
    {
        $typeEtablissement->delete();
        return $this->success(null, 'Type supprime.');
    }
}
