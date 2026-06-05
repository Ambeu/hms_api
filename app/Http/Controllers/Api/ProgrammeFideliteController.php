<?php

namespace App\Http\Controllers\Api;

use App\Models\ProgrammeFidelite;
use Illuminate\Http\Request;

class ProgrammeFideliteController extends BaseController
{
    public function index()
    {
        return $this->success(ProgrammeFidelite::orderBy('points_min')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'niveau'     => 'required|string|max:50|unique:programme_fidelites,niveau',
            'points_min' => 'required|integer|min:0',
            'points_max' => 'nullable|integer|gt:points_min',
            'remise_pct' => 'required|numeric|min:0|max:100',
            'avantages'  => 'nullable|array',
        ]);

        return $this->success(ProgrammeFidelite::create($data), 'Niveau cree.', 201);
    }

    public function show(ProgrammeFidelite $programmeFidelite)
    {
        return $this->success($programmeFidelite);
    }

    public function update(Request $request, ProgrammeFidelite $programmeFidelite)
    {
        $data = $request->validate([
            'niveau'     => 'sometimes|string|max:50|unique:programme_fidelites,niveau,' . $programmeFidelite->id,
            'points_min' => 'sometimes|integer|min:0',
            'points_max' => 'nullable|integer',
            'remise_pct' => 'sometimes|numeric|min:0|max:100',
            'avantages'  => 'nullable|array',
        ]);

        $programmeFidelite->update($data);
        return $this->success($programmeFidelite, 'Niveau mis a jour.');
    }

    public function destroy(ProgrammeFidelite $programmeFidelite)
    {
        $programmeFidelite->delete();
        return $this->success(null, 'Niveau supprime.');
    }
}
