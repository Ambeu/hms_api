<?php

namespace App\Http\Controllers\Api;

use App\Models\StockArticle;
use Illuminate\Http\Request;

class StockArticleController extends BaseController
{
    public function index(Request $request)
    {
        $query = StockArticle::whereHas('article', fn($q) => $q->where('etablissement_id', $this->etabId()))
            ->with('article.categorie');

        if ($request->boolean('alerte')) {
            $query->whereColumn('quantite_disponible', '<=', 'seuil_alerte');
        }

        return $this->success($query->get());
    }

    public function update(Request $request, StockArticle $stockArticle)
    {
        $data = $request->validate([
            'quantite_disponible' => 'required|integer|min:0',
            'seuil_alerte'        => 'nullable|integer|min:0',
        ]);

        $stockArticle->update(array_merge($data, ['mise_a_jour' => now()]));
        return $this->success($stockArticle->load('article'), 'Stock mis a jour.');
    }

    public function ajuster(Request $request, StockArticle $stockArticle)
    {
        $data = $request->validate([
            'quantite' => 'required|integer',
        ]);

        $nouvelle = max(0, $stockArticle->quantite_disponible + $data['quantite']);
        $stockArticle->update(['quantite_disponible' => $nouvelle, 'mise_a_jour' => now()]);

        return $this->success($stockArticle->load('article'), 'Stock ajuste.');
    }
}
