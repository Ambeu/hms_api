<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Clients : ajout des champs d'authentification + lien établissement
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('etablissement_id')
                ->nullable()->after('id')
                ->constrained('etablissements')->nullOnDelete();
            $table->string('password')->nullable()->after('email');
            $table->rememberToken()->after('password');
        });

        // 2. avis_clients : reservation_id devient nullable + ajout etablissement_id
        DB::statement('ALTER TABLE avis_clients MODIFY COLUMN reservation_id BIGINT UNSIGNED NULL');
        Schema::table('avis_clients', function (Blueprint $table) {
            $table->foreignId('etablissement_id')
                ->nullable()->after('client_id')
                ->constrained('etablissements')->cascadeOnDelete();
        });

        // 3. objet_oublies : chambre_id nullable + champ libre numero_chambre
        DB::statement('ALTER TABLE objet_oublies MODIFY COLUMN chambre_id BIGINT UNSIGNED NULL');
        Schema::table('objet_oublies', function (Blueprint $table) {
            $table->string('numero_chambre')->nullable()->after('chambre_id');
            $table->foreignId('etablissement_id')
                ->nullable()->after('numero_chambre')
                ->constrained('etablissements')->nullOnDelete();
            $table->string('contact_restitution')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('objet_oublies', function (Blueprint $table) {
            $table->dropForeign(['etablissement_id']);
            $table->dropColumn(['etablissement_id', 'numero_chambre', 'contact_restitution']);
        });
        DB::statement('ALTER TABLE objet_oublies MODIFY COLUMN chambre_id BIGINT UNSIGNED NOT NULL');

        Schema::table('avis_clients', function (Blueprint $table) {
            $table->dropForeign(['etablissement_id']);
            $table->dropColumn('etablissement_id');
        });
        DB::statement('ALTER TABLE avis_clients MODIFY COLUMN reservation_id BIGINT UNSIGNED NOT NULL');

        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['etablissement_id']);
            $table->dropColumn(['etablissement_id', 'password', 'remember_token']);
        });
    }
};
