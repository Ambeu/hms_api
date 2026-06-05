<?php

namespace App\Http\Controllers\Api;

use App\Models\Etablissement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends BaseController
{
    public function register(Request $request)
    {
        $data = $request->validate([
            // Compte utilisateur
            'nom'                   => 'required|string|max:100',
            'prenom'                => 'required|string|max:100',
            'email'                 => 'required|email|unique:users,email',
            'phone'                 => 'nullable|string|max:20',
            'password'              => 'required|string|min:8|confirmed',

            // �tablissement
            'etablissement.nom'                   => 'required|string|max:150',
            'etablissement.type_etablissement_id' => 'nullable|exists:type_etablissements,id',
            'etablissement.devise_id'             => 'nullable|exists:devises,id',
            'etablissement.adresse'               => 'nullable|string',
            'etablissement.telephone'             => 'nullable|string|max:20',
            'etablissement.whatsapp'              => 'nullable|string|max:20',
            'etablissement.email'                 => 'nullable|email',
            'etablissement.site_web'              => 'nullable|url',
            'etablissement.nombre_etoiles'        => 'nullable|integer|between:1,5',
            'etablissement.nb_etages'             => 'nullable|integer|min:1',
            'etablissement.latitude'              => 'nullable|numeric|between:-90,90',
            'etablissement.longitude'             => 'nullable|numeric|between:-180,180',
            'etablissement.numero_registre'       => 'nullable|string|max:100',
            'etablissement.numero_fiscal'         => 'nullable|string|max:100',
            'etablissement.date_ouverture'        => 'nullable|date',
        ]);

        DB::beginTransaction();

        try {
            // 1. Cr�er l'�tablissement
            $etabData         = $data['etablissement'];
            $etabData['slug'] = Str::slug($etabData['nom']);
            $etablissement    = Etablissement::create($etabData);

            // 2. Cr�er l'utilisateur
            $user = User::create([
                'nom'                      => $data['nom'],
                'prenom'                   => $data['prenom'],
                'name'                     => $data['prenom'] . ' ' . $data['nom'],
                'email'                    => $data['email'],
                'phone'                    => $data['phone'] ?? null,
                'password'                 => Hash::make($data['password']),
                'role'                     => 'admin',
                'actif'                    => true,
                'current_etablissement_id' => $etablissement->id,
            ]);

            // 3. Lier l'utilisateur � l'�tablissement en tant qu'admin
            $user->etablissements()->attach($etablissement->id, ['role' => 'admin']);
            $user->assignRole('admin');

            DB::commit();
        } catch (\Throwable) {
            DB::rollBack();
            return $this->error('Erreur lors de la cr�ation du compte. Veuillez r�essayer.', 500);
        }

        // 4. �mettre le token JWT (hors transaction)
        $token = auth('api')->login($user);

        return $this->success([
            'token'         => $token,
            'token_type'    => 'bearer',
            'expires_in'    => config('jwt.ttl', 60) * 60,
            'user'          => $this->userData($user),
            'etablissement' => $etablissement,
        ], 'Compte et �tablissement cr��s avec succ�s.', 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('Identifiants incorrects.', 401);
        }

        if (!$user->actif) {
            return $this->error('Compte d�sactiv�. Contactez votre administrateur.', 403);
        }

        $user->update(['derniere_connexion' => now()]);

        $token = auth('api')->login($user);

        return $this->success([
            'token'      => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user'       => $this->userData($user),
        ], 'Connexion r�ussie.');
    }

    public function me()
    {
        return $this->success($this->userData(auth()->user()));
    }

    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $data = $request->validate([
            'nom'    => 'sometimes|string|max:100',
            'prenom' => 'sometimes|string|max:100',
            'phone'  => 'nullable|string|max:20',
            'email'  => 'sometimes|email|unique:users,email,' . $user->id,
        ]);

        if (isset($data['nom']) || isset($data['prenom'])) {
            $nom    = $data['nom']    ?? $user->nom;
            $prenom = $data['prenom'] ?? $user->prenom;
            $data['name'] = $prenom . ' ' . $nom;
        }

        $user->update($data);

        return $this->success($this->userData($user), 'Profil mis à jour.');
    }

    public function updatePassword(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $request->validate([
            'mot_de_passe_actuel' => 'required|string',
            'nouveau_mot_de_passe'=> 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($request->mot_de_passe_actuel, $user->password)) {
            return $this->error('Mot de passe actuel incorrect.', 422);
        }

        $user->update(['password' => Hash::make($request->nouveau_mot_de_passe)]);

        return $this->success(null, 'Mot de passe modifié avec succès.');
    }

    public function logout()
    {
        auth('api')->logout();
        return $this->success(null, 'D�connexion r�ussie.');
    }

    public function refresh()
    {
        $token = auth('api')->refresh();
        return $this->success([
            'token'      => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }

    public function switchEtablissement(Request $request)
    {
        $request->validate([
            'etablissement_id' => 'required|integer|exists:etablissements,id',
        ]);

        $user = auth()->user();

        if (!$user->switchEtablissement($request->etablissement_id)) {
            return $this->error('Acc�s refus� � cet �tablissement.', 403);
        }

        // Invalider le token actuel et en �mettre un nouveau avec le bon etablissement_id
        auth('api')->invalidate();
        $token = auth('api')->login($user);

        return $this->success([
            'token'            => $token,
            'token_type'       => 'bearer',
            'expires_in'       => auth('api')->factory()->getTTL() * 60,
            'etablissement_id' => $request->etablissement_id,
        ], '�tablissement chang�.');
    }

    private function userData(User $user): array
    {
        return [
            'id'                       => $user->id,
            'nom_complet'              => $user->nom_complet,
            'nom'                      => $user->nom,
            'prenom'                   => $user->prenom,
            'phone'                    => $user->phone,
            'email'                    => $user->email,
            'role'                     => $user->role,
            'current_etablissement_id' => $user->current_etablissement_id,
            'etablissements'           => $user->etablissements()->select('etablissements.id', 'nom', 'slug')->get(),
            'roles'                    => $user->getRoleNames(),
            'permissions'              => $user->getAllPermissions()->pluck('name'),
        ];
    }
}
