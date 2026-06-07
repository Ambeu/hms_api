<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter la colonne unite
        Schema::table('reservations', function (Blueprint $table) {
            // $table->string('unite')->default('nuit')->after('statut');
        });

        // Convertir date_arrivee et date_depart de DATE en DATETIME
        DB::statement('ALTER TABLE reservations MODIFY COLUMN date_arrivee DATETIME NOT NULL');
        DB::statement('ALTER TABLE reservations MODIFY COLUMN date_depart DATETIME NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE reservations MODIFY COLUMN date_arrivee DATE NOT NULL');
        DB::statement('ALTER TABLE reservations MODIFY COLUMN date_depart DATE NOT NULL');

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('unite');
        });
    }
};
