<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Permissions référentiels ───────────────────────────────────────
        $referentiels = [
            'devises.view', 'devises.manage',
            'type-etablissements.view', 'type-etablissements.manage',
            'programme-fidelites.view', 'programme-fidelites.manage',
            'canal-distributions.view', 'canal-distributions.manage',
        ];

        // ── Permissions établissement ──────────────────────────────────────
        $etablissement = [
            'etablissement.view', 'etablissement.manage',
            'etages.manage', 'chambres.manage', 'tarifs.manage',
            'clients.view', 'clients.manage',
            'reservations.view', 'reservations.manage',
            'checkin.manage',
            'factures.view', 'factures.manage',
            'menage.view', 'menage.manage',
            'restauration.view', 'restauration.manage',
            'stock.manage',
            'utilisateurs.manage',
            'avis.view', 'avis.manage',
            'fidelite.manage',
        ];

        foreach (array_merge($referentiels, $etablissement) as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'api']);
        }

        // ── Rôles ─────────────────────────────────────────────────────────
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'api']);
        $superAdmin->syncPermissions(Permission::all());

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $admin->syncPermissions($etablissement);

        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'api']);
        $manager->syncPermissions([
            'etablissement.view',
            'clients.view', 'clients.manage',
            'reservations.view', 'reservations.manage',
            'checkin.manage',
            'factures.view', 'factures.manage',
            'menage.view', 'menage.manage',
            'restaurant.view', 'restaurant.manage',
            'stock.manage',
            'avis.view',
        ]);

        $reception = Role::firstOrCreate(['name' => 'reception', 'guard_name' => 'api']);
        $reception->syncPermissions([
            'clients.view', 'clients.manage',
            'reservations.view', 'reservations.manage',
            'checkin.manage',
            'factures.view',
        ]);

        $menage = Role::firstOrCreate(['name' => 'menage', 'guard_name' => 'api']);
        $menage->syncPermissions(['menage.view', 'menage.manage']);

        $restauration = Role::firstOrCreate(['name' => 'restauration', 'guard_name' => 'api']);
        $restauration->syncPermissions(['restaurant.view', 'restaurant.manage', 'stock.manage']);
    }
}
