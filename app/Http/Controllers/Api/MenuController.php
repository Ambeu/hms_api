<?php

namespace App\Http\Controllers\Api;

use App\Models\ArticleMenu;
use App\Models\Menu;
use App\Models\MenuImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MenuController extends BaseController
{
    public function index(Request $request)
    {
        $query = Menu::where('etablissement_id', $this->etabId());

        if ($request->filled('type_repas')) {
            $query->where('type_repas', $request->type_repas);
        }

        return $this->success($query->with(['images', 'articleMenus.categorie'])->orderBy('nom')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom'         => 'required|string|max:100',
            'description' => 'nullable|string',
            'type_repas'  => 'required|in:petit_dejeuner,dejeuner,diner,brunch,snack',
            'actif'       => 'boolean',
            'articles'    => 'nullable|array',
            'articles.*'  => 'exists:article_menus,id',
            'images'      => 'nullable|array|max:10',
            'images.*'    => 'file|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $data['etablissement_id'] = $this->etabId();
        $articles = $data['articles'] ?? [];
        unset($data['articles']);

        $menu = Menu::create($data);

        if (!empty($articles)) {
            $this->verifyArticlesOwnership($articles);
            $menu->articleMenus()->attach($articles);
        }

        if ($request->hasFile('images')) {
            $this->sauvegarderImages($request->file('images'), $menu);
        }

        return $this->success($menu->load(['images', 'articleMenus.categorie']), 'Menu créé.', 201);
    }

    public function show(Menu $menu)
    {
        $this->checkOwnership($menu);
        return $this->success($menu->load(['images', 'articleMenus.categorie', 'articleMenus.stock']));
    }

    public function update(Request $request, Menu $menu)
    {
        $this->checkOwnership($menu);

        $data = $request->validate([
            'nom'         => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'type_repas'  => 'sometimes|in:petit_dejeuner,dejeuner,diner,brunch,snack',
            'actif'       => 'boolean',
            'images'      => 'nullable|array|max:10',
            'images.*'    => 'file|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $menu->update($data);

        if ($request->hasFile('images')) {
            $this->sauvegarderImages($request->file('images'), $menu);
        }

        return $this->success($menu->fresh(['images', 'articleMenus.categorie']), 'Menu mis à jour.');
    }

    public function attacher(Request $request, Menu $menu)
    {
        $this->checkOwnership($menu);

        $data = $request->validate([
            'articles'   => 'required|array|min:1',
            'articles.*' => 'exists:article_menus,id',
        ]);

        $this->verifyArticlesOwnership($data['articles']);
        $menu->articleMenus()->syncWithoutDetaching($data['articles']);

        return $this->success($menu->load('articleMenus.categorie'), 'Articles ajoutés au menu.');
    }

    public function detacher(Request $request, Menu $menu)
    {
        $this->checkOwnership($menu);

        $data = $request->validate([
            'articles'   => 'required|array|min:1',
            'articles.*' => 'exists:article_menus,id',
        ]);

        $menu->articleMenus()->detach($data['articles']);

        return $this->success($menu->load('articleMenus.categorie'), 'Articles retirés du menu.');
    }

    public function destroy(Menu $menu)
    {
        $this->checkOwnership($menu);
        $menu->delete();
        return $this->success(null, 'Menu supprimé.');
    }

    // ─── Images ───────────────────────────────────────────────────────────────

    public function ajouterImages(Request $request, Menu $menu)
    {
        $this->checkOwnership($menu);

        $request->validate([
            'images'     => 'required|array|min:1|max:10',
            'images.*'   => 'required|file|mimes:jpeg,jpg,png,webp|max:5120',
            'principale' => 'nullable|integer|min:0',
        ]);

        $offsetOrdre = $menu->images()->count();

        foreach ($request->file('images') as $index => $file) {
            $path = $file->store("menus/{$menu->id}", 'public');
            $url  = Storage::url($path);

            $estPrincipale = ((int) $request->input('principale', 0)) === $index
                && $menu->images()->where('principale', true)->doesntExist();

            if ($estPrincipale) {
                $menu->images()->update(['principale' => false]);
            }

            MenuImage::create([
                'menu_id'    => $menu->id,
                'url'        => $url,
                'ordre'      => $offsetOrdre + $index,
                'principale' => $estPrincipale,
            ]);
        }

        return $this->success($menu->fresh('images'), 'Images téléversées.', 201);
    }

    public function supprimerImage(Menu $menu, MenuImage $menuImage)
    {
        $this->checkOwnership($menu);
        if ($menuImage->menu_id !== $menu->id) abort(404);

        $path = str_replace('storage/', '', ltrim(parse_url($menuImage->url, PHP_URL_PATH), '/'));
        Storage::disk('public')->delete($path);

        $menuImage->delete();
        return $this->success(null, 'Image supprimée.');
    }

    public function definirPrincipale(Menu $menu, MenuImage $menuImage)
    {
        $this->checkOwnership($menu);
        if ($menuImage->menu_id !== $menu->id) abort(404);

        $menu->images()->update(['principale' => false]);
        $menuImage->update(['principale' => true]);

        return $this->success($menu->fresh('images'), 'Image principale définie.');
    }

    private function sauvegarderImages(array $files, Menu $menu): void
    {
        $offset = $menu->images()->count();
        $sansPrincipale = $menu->images()->where('principale', true)->doesntExist();

        foreach ($files as $index => $file) {
            $url = Storage::url($file->store("menus/{$menu->id}", 'public'));
            MenuImage::create([
                'menu_id'    => $menu->id,
                'url'        => $url,
                'ordre'      => $offset + $index,
                'principale' => $sansPrincipale && $index === 0,
            ]);
        }
    }

    private function checkOwnership(Menu $menu): void
    {
        if ($menu->etablissement_id !== $this->etabId()) abort(403);
    }

    private function verifyArticlesOwnership(array $articleIds): void
    {
        $foreign = ArticleMenu::whereIn('id', $articleIds)
            ->where('etablissement_id', '!=', $this->etabId())
            ->exists();

        if ($foreign) abort(403, 'Un ou plusieurs articles n\'appartiennent pas à cet établissement.');
    }
}
