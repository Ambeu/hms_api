<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends BaseController
{
    public function index()
    {
        $users = User::whereHas('etablissements', fn($q) => $q->where('etablissement_id', $this->etabId()))
            ->with(['etablissements' => fn($q) => $q->where('etablissement_id', $this->etabId())])
            ->where('actif', true)
            ->get();

        return $this->success($users);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom'      => 'required|string|max:100',
            'prenom'   => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'nullable|string|max:20',
            'password' => 'required|string|min:5|confirmed',
            'role'     => 'required|in:admin,manager,reception,menage,restauration',
        ]);

        $user = User::create([
            'nom'                      => $data['nom'],
            'prenom'                   => $data['prenom'],
            'name'                     => $data['prenom'] . ' ' . $data['nom'],
            'email'                    => $data['email'],
            'phone'                    => $data['phone'] ?? null,
            'password'                 => Hash::make($data['password']),
            'role'                     => $data['role'],
            'actif'                    => true,
            'current_etablissement_id' => $this->etabId(),
        ]);

        $user->etablissements()->attach($this->etabId(), ['role' => $data['role']]);
        $user->assignRole($data['role']);

        return $this->success($user, 'Utilisateur cree.', 201);
    }

    public function show(User $user)
    {
        return $this->success($user->load('etablissements'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'nom'    => 'sometimes|string|max:100',
            'prenom' => 'sometimes|string|max:100',
            'email'  => 'sometimes|email|unique:users,email,' . $user->id,
            'phone'  => 'nullable|string|max:20',
            'role'   => 'sometimes|in:admin,manager,reception,menage,restauration',
            'actif'  => 'boolean',
        ]);

        $user->update($data);

        if (isset($data['role'])) {
            $user->etablissements()->updateExistingPivot($this->etabId(), ['role' => $data['role']]);
            $user->syncRoles([$data['role']]);
        }

        return $this->success($user, 'Utilisateur mis a jour.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return $this->error('Vous ne pouvez pas vous supprimer vous-meme.', 422);
        }

        $user->etablissements()->detach($this->etabId());

        if ($user->etablissements()->count() === 0) {
            $user->update(['actif' => false]);
        }

        return $this->success(null, 'Utilisateur retire de l\'etablissement.');
    }
}
