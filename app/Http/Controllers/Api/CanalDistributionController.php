<?php

namespace App\Http\Controllers\Api;

use App\Models\CanalDistribution;
use Illuminate\Http\Request;

class CanalDistributionController extends BaseController
{
    public function index()
    {
        return $this->success(CanalDistribution::orderBy('nom')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom'          => 'required|string|max:100',
            'type'         => 'required|string|max:50',
            'api_endpoint' => 'nullable|url',
            'api_key_hash' => 'nullable|string',
            'actif'        => 'boolean',
        ]);

        return $this->success(CanalDistribution::create($data), 'Canal cree.', 201);
    }

    public function show(CanalDistribution $canalDistribution)
    {
        return $this->success($canalDistribution);
    }

    public function update(Request $request, CanalDistribution $canalDistribution)
    {
        $data = $request->validate([
            'nom'          => 'sometimes|string|max:100',
            'type'         => 'sometimes|string|max:50',
            'api_endpoint' => 'nullable|url',
            'api_key_hash' => 'nullable|string',
            'actif'        => 'boolean',
        ]);

        $canalDistribution->update($data);
        return $this->success($canalDistribution, 'Canal mis a jour.');
    }

    public function destroy(CanalDistribution $canalDistribution)
    {
        $canalDistribution->delete();
        return $this->success(null, 'Canal supprime.');
    }
}
