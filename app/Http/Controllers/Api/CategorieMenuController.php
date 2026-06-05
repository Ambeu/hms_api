<?php

namespace App\Http\Controllers\Api;

use App\Models\CategorieMenu;
use Illuminate\Http\Request;

class CategorieMenuController extends BaseController
{
    public function index()
    {
        return $this->success(
            CategorieMenu::where('etablissement_id', $this->etabId())
                ->orderBy('ordre')
                ->orderBy('nom')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom'         => 'required|string|max:100',
            'description' => 'nullable|string',
            'icone'       => 'nullable|string|max:50',
            'ordre'       => 'nullable|integer|min:0',
            'actif'       => 'boolean',
        ]);

        $data['etablissement_id'] = $this->etabId();
        return $this->success(CategorieMenu::create($data), 'Catégorie créée.', 201);
    }

    public function show(CategorieMenu $categorieMenu)
    {
        $this->checkOwnership($categorieMenu);
        return $this->success($categorieMenu->load('articles'));
    }

    public function update(Request $request, CategorieMenu $categorieMenu)
    {
        $this->checkOwnership($categorieMenu);

        $data = $request->validate([
            'nom'         => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'icone'       => 'nullable|string|max:50',
            'ordre'       => 'nullable|integer|min:0',
            'actif'       => 'boolean',
        ]);

        $categorieMenu->update($data);
        return $this->success($categorieMenu, 'Catégorie mise à jour.');
    }

    public function destroy(CategorieMenu $categorieMenu)
    {
        $this->checkOwnership($categorieMenu);
        $categorieMenu->delete();
        return $this->success(null, 'Catégorie supprimée.');
    }

    private function checkOwnership(CategorieMenu $categorieMenu): void
    {
        if ($categorieMenu->etablissement_id !== $this->etabId()) abort(403);
    }
}

