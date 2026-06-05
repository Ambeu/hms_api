<?php

namespace App\Http\Controllers\Api\Public;

use App\Models\Client;
use App\Models\Etablissement;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;

class ClientAuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'slug'      => 'required|exists:etablissements,slug',
            'prenom'    => 'required|string|max:100',
            'nom'       => 'required|string|max:100',
            'email'     => 'required|email|max:150',
            'telephone' => 'nullable|string|max:20',
            'password'  => 'required|string|min:6|confirmed',
        ]);

        $etab = Etablissement::where('slug', $data['slug'])->firstOrFail();

        if (Client::where('email', $data['email'])->where('etablissement_id', $etab->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Un compte existe deja avec cet email.'], 422);
        }

        $client = Client::create([
            'etablissement_id' => $etab->id,
            'prenom'           => $data['prenom'],
            'nom'              => $data['nom'],
            'email'            => $data['email'],
            'telephone'        => $data['telephone'] ?? null,
            'password'         => Hash::make($data['password']),
        ]);

        $token = auth('client')->login($client);

        return response()->json([
            'success'    => true,
            'message'    => 'Compte cree avec succes.',
            'data'       => ['token' => $token, 'expires_in' => config('jwt.ttl', 60) * 60, 'client' => $client],
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
            'slug'     => 'nullable|exists:etablissements,slug',
        ]);

        $query = Client::where('email', $data['email'])->whereNotNull('password');

        if (!empty($data['slug'])) {
            $etab  = Etablissement::where('slug', $data['slug'])->firstOrFail();
            $query->where('etablissement_id', $etab->id);
        }

        $client = $query->first();

        if (!$client || !Hash::check($data['password'], $client->password)) {
            return response()->json(['success' => false, 'message' => 'Email ou mot de passe incorrect.'], 401);
        }

        $token = auth('client')->login($client);

        return response()->json([
            'success' => true,
            'data'    => ['token' => $token, 'expires_in' => config('jwt.ttl', 60) * 60, 'client' => $client],
        ]);
    }

    public function me()
    {
        return response()->json(['success' => true, 'data' => auth('client')->user()]);
    }

    public function logout()
    {
        auth('client')->logout();
        return response()->json(['success' => true, 'message' => 'Deconnecte.']);
    }
}
