<?php

namespace App\Http\Controllers\Api;

use App\Mail\BienvenuClientMail;
use App\Models\Client;
use App\Models\Etablissement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ClientController extends BaseController
{
    public function index(Request $request)
    {
        $query = Client::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nom', 'like', "%{$request->search}%")
                  ->orWhere('prenom', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('telephone', 'like', "%{$request->search}%")
                  ->orWhere('numero_document', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('segment')) {
            $query->where('segment', $request->segment);
        }

        return $this->success($query->orderBy('nom')->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom'             => 'required|string|max:100',
            'prenom'          => 'required|string|max:100',
            'email'           => 'nullable|email|unique:clients,email',
            'telephone'       => 'nullable|string|max:20',
            'nationalite'     => 'nullable|string|max:100',
            'type_document'   => 'nullable|in:passeport,cni,permis,autre',
            'numero_document' => 'nullable|string|max:50',
            'date_naissance'  => 'nullable|date|before:today',
            'segment'         => 'nullable|in:standard,vip,corporate,fidelise',
            'preferences'     => 'nullable|array',
        ]);

        $motDePasse = ucfirst(strtolower(substr($data['prenom'], 0, 3))) . rand(1000, 9999) . '!';
        $data['etablissement_id'] = $this->etabId();
        $data['password']         = Hash::make($motDePasse);

        $client = Client::create($data);

        if ($client->email) {
            $etablissement = Etablissement::find($this->etabId());
            Mail::to($client->email)
                ->queue(new BienvenuClientMail($client, $motDePasse, $etablissement));
        }

        return $this->success($client, 'Client cree.', 201);
    }

    public function show(Client $client)
    {
        return $this->success($client->load(['reservations' => fn($q) => $q->latest()->limit(10), 'avisClients']));
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'nom'             => 'sometimes|string|max:100',
            'prenom'          => 'sometimes|string|max:100',
            'email'           => 'nullable|email|unique:clients,email,' . $client->id,
            'telephone'       => 'nullable|string|max:20',
            'nationalite'     => 'nullable|string|max:100',
            'type_document'   => 'nullable|in:passeport,cni,permis,autre',
            'numero_document' => 'nullable|string|max:50',
            'date_naissance'  => 'nullable|date|before:today',
            'segment'         => 'nullable|in:standard,vip,corporate,fidelise',
            'preferences'     => 'nullable|array',
        ]);

        $client->update($data);
        return $this->success($client, 'Client mis a jour.');
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return $this->success(null, 'Client supprime.');
    }
}
