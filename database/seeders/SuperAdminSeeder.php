<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@hms.cm'],
            [
                'name'                     => 'Super Administrateur',
                'nom'                      => 'Administrateur',
                'prenom'                   => 'Super',
                'password'                 => Hash::make('SuperAdmin@2026!'),
                'role'                     => 'super_admin',
                'actif'                    => true,
                'current_etablissement_id' => null,
            ]
        );

        // Assigner le rôle Spatie (guard api)
        $superAdmin->assignRole('super_admin');

        $this->command->info('✅ Super Admin créé : superadmin@hms.cm / SuperAdmin@2026!');
    }
}
