<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesPermissionsSeeder::class, // 1. Rôles et permissions Spatie
            ReferenceDataSeeder::class,    // 2. Devises, types, fidélité, canaux
            SuperAdminSeeder::class,       // 3. Compte super admin
        ]);
    }
}
