<?php

namespace App\Http\Controllers\Api;

use App\Models\Etablissement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EtablissementController extends BaseController
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return $this->success(
            $user->etablissements()->with(['typeEtablissement', 'devise'])->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type_etablissement_id' => 'nullable|exists:type_etablissements,id',
            'devise_id'             => 'nullable|exists:devises,id',
            'nom'                   => 'required|string|max:150',
            'slug'                  => 'nullable|string|max:150',
            'adresse'               => 'nullable|string',
            'telephone'             => 'nullable|string|max:20',
            'whatsapp'              => 'nullable|string|max:20',
            'email'                 => 'nullable|email',
            'site_web'              => 'nullable|url',
            'nombre_etoiles'        => 'nullable|integer|min:0|max:5',
            'nb_etages'             => 'nullable|integer',
            'image_couverture'      => 'nullable|file|mimes:jpeg,jpg,png,webp|max:5120',
            'logo_url'              => 'nullable|file|mimes:jpeg,jpg,png,webp,svg|max:2048',
            'latitude'              => 'nullable|numeric|between:-90,90',
            'longitude'             => 'nullable|numeric|between:-180,180',
            'numero_registre'       => 'nullable|string|max:100',
            'numero_fiscal'         => 'nullable|string|max:100',
            'date_ouverture'        => 'nullable|date',
            'taux_tva'              => 'nullable|numeric|min:0|max:100',
        ]);

        $data['slug'] = $data['slug']
            ? Etablissement::genererSlugUnique($data['slug'])
            : Etablissement::genererSlugUnique($data['nom']);

        if ($request->hasFile('image_couverture')) {
            $data['image_couverture'] = Storage::url(
                $request->file('image_couverture')->store('etablissements/couvertures', 'public')
            );
        }

        if ($request->hasFile('logo_url')) {
            $data['logo_url'] = Storage::url(
                $request->file('logo_url')->store('etablissements/logos', 'public')
            );
        }

        $etablissement = Etablissement::create($data);

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $user->etablissements()->attach($etablissement->id, ['role' => 'admin']);
        $user->update(['current_etablissement_id' => $etablissement->id]);

        return $this->success($etablissement->load(['typeEtablissement', 'devise']), 'Etablissement cree.', 201);
    }

    public function show(Etablissement $etablissement)
    {
        $this->checkAccess($etablissement);
        return $this->success($etablissement->load(['typeEtablissement', 'devise', 'etages']));
    }

    public function update(Request $request, Etablissement $etablissement)
    {
        $this->checkAccess($etablissement);

        $data = $request->validate([
            'type_etablissement_id' => 'nullable|exists:type_etablissements,id',
            'devise_id'             => 'nullable|exists:devises,id',
            'nom'                   => 'sometimes|string|max:150',
            'slug'                  => 'nullable|string|max:150|unique:etablissements,slug,' . $etablissement->id,
            'adresse'               => 'nullable|string',
            'telephone'             => 'nullable|string|max:20',
            'whatsapp'              => 'nullable|string|max:20',
            'email'                 => 'nullable|email',
            'site_web'              => 'nullable|url',
            'nombre_etoiles'        => 'nullable|integer|min:1|max:5',
            'nb_etages'             => 'nullable|integer',
            'image_couverture'      => 'nullable|file|mimes:jpeg,jpg,png,webp|max:5120',
            'logo_url'              => 'nullable|file|mimes:jpeg,jpg,png,webp,svg|max:2048',
            'latitude'              => 'nullable|numeric|between:-90,90',
            'longitude'             => 'nullable|numeric|between:-180,180',
            'numero_registre'       => 'nullable|string|max:100',
            'numero_fiscal'         => 'nullable|string|max:100',
            'date_ouverture'        => 'nullable|date',
            'taux_tva'              => 'nullable|numeric|min:0|max:100',
        ]);

        if (isset($data['slug'])) {
            $data['slug'] = Etablissement::genererSlugUnique($data['slug'], $etablissement->id);
        }

        if ($request->hasFile('image_couverture')) {
            if ($etablissement->image_couverture) {
                Storage::disk('public')->delete(
                    str_replace('/storage/', '', parse_url($etablissement->image_couverture, PHP_URL_PATH))
                );
            }
            $data['image_couverture'] = Storage::url(
                $request->file('image_couverture')->store('etablissements/couvertures', 'public')
            );
        }

        if ($request->hasFile('logo_url')) {
            if ($etablissement->logo_url) {
                Storage::disk('public')->delete(
                    str_replace('/storage/', '', parse_url($etablissement->logo_url, PHP_URL_PATH))
                );
            }
            $data['logo_url'] = Storage::url(
                $request->file('logo_url')->store('etablissements/logos', 'public')
            );
        }

        $etablissement->update($data);
        return $this->success($etablissement->fresh(['typeEtablissement', 'devise']), 'Etablissement mis a jour.');
    }

    public function destroy(Etablissement $etablissement)
    {
        $this->checkAccess($etablissement);
        $etablissement->delete();
        return $this->success(null, 'Etablissement supprime.');
    }

    private function checkAccess(Etablissement $etablissement): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (!$user->hasAccessTo($etablissement->id)) {
            abort(403, 'Acces refuse a cet etablissement.');
        }
    }
}
