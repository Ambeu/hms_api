<?php

namespace App\Http\Controllers\Api;

use App\Models\TacheMenage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TacheMenageController extends BaseController
{
    public function index(Request $request)
    {
        $query = TacheMenage::whereHas('chambre', fn($q) => $q->where('etablissement_id', $this->etabId()))
            ->with(['chambre.etage', 'agent']);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('agent_id')) {
            $query->where('agent_id', $request->agent_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('date_assignation', $request->date);
        }

        return $this->success($query->latest('date_assignation')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'chambre_id'  => 'required|exists:chambres,id',
            'agent_id'    => 'nullable|exists:users,id',
            'type_tache'  => 'required|in:nettoyage,depart,arrivee,inspection',
            'checklist'   => 'nullable|array',
            'observations'=> 'nullable|string',
        ]);

        $data['date_assignation'] = now();
        $data['statut']           = empty($data['agent_id']) ? 'non_assignee' : 'assignee';
        $data['lien_token']       = Str::uuid()->toString();

        $tache = TacheMenage::create($data);
        $tache->lien_acces = $this->lienAcces($tache->lien_token);

        return $this->success($tache->load(['chambre', 'agent']), 'Tâche créée.', 201);
    }

    public function show(TacheMenage $tacheMenage)
    {
        $this->checkOwnership($tacheMenage);
        return $this->success($tacheMenage->load(['chambre.etage', 'agent']));
    }

    public function update(Request $request, TacheMenage $tacheMenage)
    {
        $this->checkOwnership($tacheMenage);

        $data = $request->validate([
            'agent_id'    => 'nullable|exists:users,id',
            'statut'      => 'sometimes|in:non_assignee,assignee,en_cours,terminee,validee',
            'observations'=> 'nullable|string',
            'checklist'   => 'nullable|array',
        ]);

        if (isset($data['statut'])) {
            if ($data['statut'] === 'en_cours' && !$tacheMenage->date_debut) {
                $data['date_debut'] = now();
                $tacheMenage->chambre->update(['statut' => 'en_nettoyage']);
            }
            if ($data['statut'] === 'terminee' && !$tacheMenage->date_fin) {
                $data['date_fin'] = now();
            }
            if (in_array($data['statut'], ['terminee', 'validee'])) {
                $tacheMenage->chambre->update(['statut' => 'disponible']);
            }
        }

        $tacheMenage->update($data);
        return $this->success($tacheMenage->fresh(['chambre', 'agent']), 'Tâche mise à jour.');
    }

    public function assigner(Request $request, TacheMenage $tacheMenage)
    {
        $this->checkOwnership($tacheMenage);

        $data = $request->validate([
            'agent_id' => 'required|exists:users,id',
        ]);

        $update = ['agent_id' => $data['agent_id']];

        if ($tacheMenage->statut === 'non_assignee') {
            $update['statut'] = 'assignee';
        }

        $tacheMenage->update($update);
        $fresh = $tacheMenage->fresh(['chambre', 'agent']);
        $fresh->lien_acces = $this->lienAcces($tacheMenage->lien_token);
        return $this->success($fresh, 'Tâche assignée.');
    }

    public function transferer(Request $request, TacheMenage $tacheMenage)
    {
        $this->checkOwnership($tacheMenage);

        $data = $request->validate([
            'agent_id' => 'required|exists:users,id',
        ]);

        if ($data['agent_id'] === $tacheMenage->agent_id) {
            return $this->error('Cet agent est déjà assigné à cette tâche.', 422);
        }

        $tacheMenage->update(['agent_id' => $data['agent_id']]);
        return $this->success($tacheMenage->fresh(['chambre', 'agent']), 'Tâche transférée.');
    }

    public function destroy(TacheMenage $tacheMenage)
    {
        $this->checkOwnership($tacheMenage);
        $tacheMenage->chambre->update(['statut' => 'disponible']);
        $tacheMenage->delete();
        return $this->success(null, 'Tâche supprimée.');
    }

    private function lienAcces(string $token): string
    {
        $frontend = rtrim(env('FRONTEND_URL', 'https://hms.andysoft.tech'), '/');
        return "{$frontend}/tache-agent/{$token}";
    }

    private function checkOwnership(TacheMenage $tacheMenage): void
    {
        if ($tacheMenage->chambre->etablissement_id !== $this->etabId()) abort(403);
    }
}

