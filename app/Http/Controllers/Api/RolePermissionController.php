<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends BaseController
{
    // ─── Rôles ───────────────────────────────────────────────────────────────

    public function roles()
    {
        $roles = Role::where('guard_name', 'api')
            ->where('name', '!=', 'super_admin')
            ->with('permissions')
            ->get()
            ->map(fn($r) => [
                'id'          => $r->id,
                'name'        => $r->name,
                'permissions' => $r->permissions->pluck('name'),
            ]);

        return $this->success($roles);
    }

    public function showRole(string $roleName)
    {
        $role = Role::where('name', $roleName)->where('guard_name', 'api')->firstOrFail();

        return $this->success([
            'id'          => $role->id,
            'name'        => $role->name,
            'permissions' => $role->permissions->pluck('name'),
            'users_count' => User::role($roleName, 'api')->count(),
        ]);
    }

    public function updateRolePermissions(Request $request, string $roleName)
    {
        if ($roleName === 'super_admin') {
            return $this->error('Les permissions du super_admin ne peuvent pas être modifiées.', 403);
        }

        $data = $request->validate([
            'permissions'   => 'required|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role = Role::where('name', $roleName)->where('guard_name', 'api')->firstOrFail();
        $role->syncPermissions($data['permissions']);

        return $this->success([
            'name'        => $role->name,
            'permissions' => $role->permissions->pluck('name'),
        ], 'Permissions du rôle mises à jour.');
    }

    // ─── Permissions ─────────────────────────────────────────────────────────

    public function permissions()
    {
        $permissions = Permission::where('guard_name', 'api')
            ->get()
            ->pluck('name')
            ->groupBy(fn($name) => explode('.', $name)[0])
            ->map(fn($group) => $group->values());

        return $this->success($permissions);
    }

    // ─── Utilisateurs ────────────────────────────────────────────────────────

    public function assignerRole(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => 'required|in:admin,manager,reception,menage,restauration',
        ]);

        $user->syncRoles([$data['role']]);
        $user->update(['role' => $data['role']]);

        if ($user->etablissements()->where('etablissement_id', $this->etabId())->exists()) {
            $user->etablissements()->updateExistingPivot($this->etabId(), ['role' => $data['role']]);
        }

        return $this->success([
            'user'        => $user->only(['id', 'nom', 'prenom', 'email', 'role']),
            'roles'       => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ], 'Rôle assigné.');
    }

    public function permissionsUtilisateur(User $user)
    {
        return $this->success([
            'user'        => $user->only(['id', 'nom', 'prenom', 'email', 'role']),
            'roles'       => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }
}
