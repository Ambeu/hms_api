<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Etablissement;

abstract class BaseController extends Controller
{
    protected function etabId(): int
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        return $user->current_etablissement_id;
    }

    protected function tvaRate(): float
    {
        $taux = Etablissement::find($this->etabId())?->taux_tva;
        return (float) ($taux ?? 18.00) / 100;
    }

    protected function calculerTva(float $montantHt): array
    {
        $taux       = $this->tvaRate();
        $tva        = round($montantHt * $taux, 2);
        $montantTtc = $montantHt + $tva;
        return compact('tva', 'montantTtc');
    }

    protected function success(mixed $data, string $message = '', int $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    protected function error(string $message, int $status = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
