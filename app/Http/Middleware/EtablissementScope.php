<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EtablissementScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user->current_etablissement_id) {
            return response()->json([
                'message' => 'Aucun établissement sélectionné. Veuillez choisir un établissement.',
            ], 403);
        }

        if (!$user->hasAccessTo($user->current_etablissement_id)) {
            return response()->json([
                'message' => 'Accès refusé à cet établissement.',
            ], 403);
        }

        return $next($request);
    }
}
