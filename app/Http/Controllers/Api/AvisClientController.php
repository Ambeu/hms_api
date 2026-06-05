<?php

namespace App\Http\Controllers\Api;

use App\Models\AvisClient;
use Illuminate\Http\Request;

class AvisClientController extends BaseController
{
    public function index(Request $request)
    {
        $query = AvisClient::whereHas('reservation', fn($q) => $q->where('etablissement_id', $this->etabId()))
            ->with(['client', 'reservation.chambre']);

        if ($request->filled('note_min')) {
            $query->where('note_globale', '>=', $request->note_min);
        }

        return $this->success($query->latest()->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id'         => 'required|exists:clients,id',
            'reservation_id'    => 'nullable|exists:reservations,id',
            'note_globale'      => 'required|integer|between:1,5',
            'note_chambre'      => 'nullable|integer|between:1,5',
            'note_service'      => 'nullable|integer|between:1,5',
            'note_restauration' => 'nullable|integer|between:1,5',
            'commentaire'       => 'nullable|string|max:1000',
        ]);

        $data['etablissement_id'] = $this->etabId();

        return $this->success(
            AvisClient::create($data)->load(['client', 'reservation']),
            'Avis enregistre.', 201
        );
    }

    public function show(AvisClient $avisClient)
    {
        return $this->success($avisClient->load(['client', 'reservation.chambre']));
    }

    public function destroy(AvisClient $avisClient)
    {
        $avisClient->delete();
        return $this->success(null, 'Avis supprime.');
    }

    public function statistiques()
    {
        $avis = AvisClient::whereHas('reservation', fn($q) => $q->where('etablissement_id', $this->etabId()));

        return $this->success([
            'note_globale_moyenne'      => round($avis->avg('note_globale'), 2),
            'note_chambre_moyenne'      => round($avis->avg('note_chambre'), 2),
            'note_service_moyenne'      => round($avis->avg('note_service'), 2),
            'note_restauration_moyenne' => round($avis->avg('note_restauration'), 2),
            'total_avis'                => $avis->count(),
        ]);
    }
}
