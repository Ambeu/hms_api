<?php

namespace App\Http\Controllers\Api\Public;

use App\Models\TacheMenage;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TachePubliqueController extends Controller
{
    private function tacheParToken(string $token): TacheMenage
    {
        return TacheMenage::where('lien_token', $token)
            ->with(['chambre.etage', 'agent'])
            ->firstOrFail();
    }

    private function verifierTelephone(TacheMenage $tache, string $telephone): bool
    {
        if (!$tache->agent) return false;

        $tel      = preg_replace('/\D/', '', $telephone);
        $agentTel = preg_replace('/\D/', '', $tache->agent->phone ?? '');

        return $tel !== '' && str_ends_with($agentTel, $tel);
    }

    /**
     * Récupérer les informations de la tâche via le token (sans auth).
     */
    public function show(string $token)
    {
        $tache = $this->tacheParToken($token);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'               => $tache->id,
                'type_tache'       => $tache->type_tache,
                'statut'           => $tache->statut,
                'date_assignation' => $tache->date_assignation,
                'date_debut'       => $tache->date_debut,
                'date_fin'         => $tache->date_fin,
                'observations'     => $tache->observations,
                'checklist'        => $tache->checklist,
                'chambre'          => [
                    'numero' => $tache->chambre->numero,
                    'etage'  => $tache->chambre->etage?->numero,
                    'statut' => $tache->chambre->statut,
                ],
                'agent' => $tache->agent ? [
                    'prenom' => $tache->agent->prenom,
                    'nom'    => $tache->agent->nom,
                ] : null,
            ],
        ]);
    }

    /**
     * Authentifier l'agent par son numéro de téléphone.
     * Retourne un token de session court (le même lien_token suffit comme clé).
     */
    public function authentifier(Request $request, string $token)
    {
        $request->validate([
            'telephone' => 'required|string',
        ]);

        $tache = $this->tacheParToken($token);

        if (!$this->verifierTelephone($tache, $request->telephone)) {
            return response()->json([
                'success' => false,
                'message' => 'Numéro de téléphone incorrect.',
            ], 401);
        }

        return response()->json([
            'success'        => true,
            'message'        => 'Authentifié.',
            'access_token'   => $token,
        ]);
    }

    /**
     * Mettre à jour le statut et/ou la checklist de la tâche.
     * Requiert le téléphone de l'agent pour valider l'identité.
     */
    public function mettreAJour(Request $request, string $token)
    {
        $data = $request->validate([
            'telephone'    => 'required|string',
            'statut'       => 'nullable|in:en_cours,terminee',
            'observations' => 'nullable|string',
            'checklist'    => 'nullable|array',
        ]);

        $tache = $this->tacheParToken($token);

        if (!$this->verifierTelephone($tache, $data['telephone'])) {
            return response()->json(['success' => false, 'message' => 'Numéro de téléphone incorrect.'], 401);
        }

        if (in_array($tache->statut, ['terminee', 'validee'])) {
            return response()->json(['success' => false, 'message' => 'Cette tâche est déjà terminée.'], 422);
        }

        $update = [];

        if (!empty($data['statut'])) {
            $update['statut'] = $data['statut'];

            if ($data['statut'] === 'en_cours' && !$tache->date_debut) {
                $update['date_debut'] = now();
                $tache->chambre->update(['statut' => 'en_nettoyage']);
            }

            if ($data['statut'] === 'terminee' && !$tache->date_fin) {
                $update['date_fin'] = now();
                $tache->chambre->update(['statut' => 'disponible']);
            }
        }

        if (isset($data['observations'])) {
            $update['observations'] = $data['observations'];
        }

        if (isset($data['checklist'])) {
            $update['checklist'] = $data['checklist'];
        }

        $tache->update($update);

        return response()->json([
            'success' => true,
            'message' => 'Tâche mise à jour.',
            'data'    => $tache->fresh(['chambre', 'agent']),
        ]);
    }
}
