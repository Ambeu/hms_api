<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commande_restaurants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained('reservations')->nullOnDelete();
            $table->string('type_commande'); // sur_place, room_service, emporter
            $table->string('statut')->default('en_attente'); // en_attente, en_preparation, servie, annulee
            $table->integer('numero_table')->nullable();
            $table->string('numero_chambre')->nullable();
            $table->decimal('montant_total', 10, 2)->default(0);
            $table->timestamp('servie_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commande_restaurants');
    }
};
