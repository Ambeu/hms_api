<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rendre reservation_id et client_id nullable (une facture peut être liée à une commande restaurant sans réservation ni client)
        DB::statement('ALTER TABLE factures MODIFY COLUMN reservation_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE factures MODIFY COLUMN client_id BIGINT UNSIGNED NULL');

        Schema::table('factures', function (Blueprint $table) {
            $table->foreignId('commande_restaurant_id')
                ->nullable()
                ->after('reservation_id')
                ->constrained('commande_restaurants')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            $table->dropForeign(['commande_restaurant_id']);
            $table->dropColumn('commande_restaurant_id');
        });

        DB::statement('ALTER TABLE factures MODIFY COLUMN reservation_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE factures MODIFY COLUMN client_id BIGINT UNSIGNED NOT NULL');
    }
};
