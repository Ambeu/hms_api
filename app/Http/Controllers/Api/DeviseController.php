<?php

namespace App\Http\Controllers\Api;

use App\Models\Devise;
use Illuminate\Http\Request;

class DeviseController extends BaseController
{
    public function index()
    {
        return $this->success(Devise::where('actif', true)->orderBy('code')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'    => 'required|string|max:10|unique:devises,code',
            'nom'     => 'required|string|max:100',
            'symbole' => 'required|string|max:10',
            'actif'   => 'boolean',
        ]);

        return $this->success(Devise::create($data), 'Devise creee.', 201);
    }

    public function show(Devise $devise)
    {
        return $this->success($devise);
    }

    public function update(Request $request, Devise $devise)
    {
        $data = $request->validate([
            'code'    => 'sometimes|string|max:10|unique:devises,code,' . $devise->id,
            'nom'     => 'sometimes|string|max:100',
            'symbole' => 'sometimes|string|max:10',
            'actif'   => 'boolean',
        ]);

        $devise->update($data);
        return $this->success($devise, 'Devise mise a jour.');
    }

    public function destroy(Devise $devise)
    {
        $devise->delete();
        return $this->success(null, 'Devise supprimee.');
    }
}
