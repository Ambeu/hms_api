<?php

namespace App\Http\Controllers\Api;

use App\Models\ArticleMenu;
use App\Models\StockArticle;
use Illuminate\Http\Request;

class ArticleMenuController extends BaseController
{
    public function index(Request $request)
    {
        $query = ArticleMenu::where('etablissement_id', $this->etabId())
            ->with(['categorie', 'stock', 'menus']);

        if ($request->filled('categorie_id')) {
            $query->where('categorie_id', $request->categorie_id);
        }

        if ($request->boolean('disponible')) {
            $query->where('disponible', true);
        }

        return $this->success($query->orderBy('nom')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'categorie_id'  => 'nullable|exists:categories_menu,id',
            'nom'           => 'required|string|max:150',
            'description'   => 'nullable|string',
            'prix'          => 'required|numeric|min:0',
            'allergenes'    => 'nullable|array',
            'disponible'    => 'boolean',
            'image_url'     => 'nullable|string',
            'stock_initial' => 'nullable|integer|min:0',
            'seuil_alerte'  => 'nullable|integer|min:0',
        ]);

        $data['etablissement_id'] = $this->etabId();

        $article = ArticleMenu::create($data);

        StockArticle::create([
            'article_id'          => $article->id,
            'quantite_disponible' => $data['stock_initial'] ?? 0,
            'seuil_alerte'        => $data['seuil_alerte'] ?? 5,
        ]);

        return $this->success($article->load(['categorie', 'stock']), 'Article créé.', 201);
    }

    public function show(ArticleMenu $articleMenu)
    {
        $this->checkOwnership($articleMenu);
        return $this->success($articleMenu->load(['categorie', 'stock', 'menus']));
    }

    public function update(Request $request, ArticleMenu $articleMenu)
    {
        $this->checkOwnership($articleMenu);

        $data = $request->validate([
            'categorie_id' => 'nullable|exists:categories_menu,id',
            'nom'          => 'sometimes|string|max:150',
            'description'  => 'nullable|string',
            'prix'         => 'sometimes|numeric|min:0',
            'allergenes'   => 'nullable|array',
            'disponible'   => 'boolean',
            'image_url'    => 'nullable|string',
        ]);

        $articleMenu->update($data);
        return $this->success($articleMenu->fresh(['categorie', 'stock']), 'Article mis à jour.');
    }

    public function destroy(ArticleMenu $articleMenu)
    {
        $this->checkOwnership($articleMenu);
        $articleMenu->delete();
        return $this->success(null, 'Article supprimé.');
    }

    private function checkOwnership(ArticleMenu $articleMenu): void
    {
        if ($articleMenu->etablissement_id !== $this->etabId()) abort(403);
    }
}
