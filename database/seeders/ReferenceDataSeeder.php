<?php

namespace Database\Seeders;

use App\Models\CanalDistribution;
use App\Models\Devise;
use App\Models\ProgrammeFidelite;
use App\Models\TypeEtablissement;
use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── Devises ───────────────────────────────────────────────────────
        $devises = [
            ['code' => 'XAF', 'nom' => 'Franc CFA BEAC',      'symbole' => 'FCFA'],
            ['code' => 'XOF', 'nom' => 'Franc CFA BCEAO',     'symbole' => 'FCFA'],
            ['code' => 'EUR', 'nom' => 'Euro',                 'symbole' => '€'],
            ['code' => 'USD', 'nom' => 'Dollar américain',    'symbole' => '$'],
            ['code' => 'GBP', 'nom' => 'Livre sterling',      'symbole' => '£'],
            ['code' => 'NGN', 'nom' => 'Naira nigérian',      'symbole' => '₦'],
            ['code' => 'GHS', 'nom' => 'Cedi ghanéen',        'symbole' => '₵'],
            ['code' => 'MAD', 'nom' => 'Dirham marocain',     'symbole' => 'DH'],
        ];

        foreach ($devises as $d) {
            Devise::firstOrCreate(['code' => $d['code']], array_merge($d, ['actif' => true]));
        }

        // ── Types d'établissement ─────────────────────────────────────────
        $types = [
            ['nom' => 'Hôtel',        'description' => 'Établissement hôtelier classique'],
            ['nom' => 'Auberge',      'description' => 'Auberge de jeunesse ou de voyageurs'],
            ['nom' => 'Resort',       'description' => 'Complexe hôtelier avec loisirs'],
            ['nom' => 'Pension',      'description' => 'Pension de famille'],
            ['nom' => 'Apparthotel', 'description' => 'Hôtel avec appartements équipés'],
            ['nom' => 'Motel',        'description' => 'Hébergement pour automobilistes'],
            ['nom' => 'Lodge',        'description' => 'Hébergement en milieu naturel'],
            ['nom' => 'Guesthouse',   'description' => 'Maison d\'hôtes'],
        ];

        foreach ($types as $t) {
            TypeEtablissement::firstOrCreate(['nom' => $t['nom']], $t);
        }

        // ── Programme de fidélité ─────────────────────────────────────────
        $niveaux = [
            [
                'niveau'     => 'Bronze',
                'points_min' => 0,
                'points_max' => 999,
                'remise_pct' => 0,
                'avantages'  => ['Accès au programme fidélité', 'Newsletter exclusive'],
            ],
            [
                'niveau'     => 'Argent',
                'points_min' => 1000,
                'points_max' => 4999,
                'remise_pct' => 5,
                'avantages'  => ['5% de réduction', 'Check-out tardif (sur demande)', 'Surclassement si disponible'],
            ],
            [
                'niveau'     => 'Or',
                'points_min' => 5000,
                'points_max' => 9999,
                'remise_pct' => 10,
                'avantages'  => ['10% de réduction', 'Petit-déjeuner offert', 'Check-out tardif garanti', 'Accès lounge'],
            ],
            [
                'niveau'     => 'Platine',
                'points_min' => 10000,
                'points_max' => null,
                'remise_pct' => 15,
                'avantages'  => ['15% de réduction', 'Suite garantie', 'Transfer aéroport offert', 'Butler dédié'],
            ],
        ];

        foreach ($niveaux as $n) {
            ProgrammeFidelite::firstOrCreate(['niveau' => $n['niveau']], $n);
        }

        // ── Canaux de distribution ────────────────────────────────────────
        $canaux = [
            ['nom' => 'Direct (site web)', 'type' => 'direct',   'actif' => true],
            ['nom' => 'Téléphone',         'type' => 'direct',   'actif' => true],
            ['nom' => 'Walk-in',           'type' => 'direct',   'actif' => true],
            ['nom' => 'Booking.com',       'type' => 'OTA',      'actif' => true],
            ['nom' => 'Expedia',           'type' => 'OTA',      'actif' => true],
            ['nom' => 'Airbnb',            'type' => 'OTA',      'actif' => true],
            ['nom' => 'Agence de voyage',  'type' => 'agence',   'actif' => true],
            ['nom' => 'Entreprise',        'type' => 'corporate', 'actif' => true],
        ];

        foreach ($canaux as $c) {
            CanalDistribution::firstOrCreate(['nom' => $c['nom']], $c);
        }
    }
}
