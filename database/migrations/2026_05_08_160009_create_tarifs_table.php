<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarifs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_chambre_id')->constrained('type_chambres')->cascadeOnDelete();
            $table->string('nom');
            $table->decimal('prix', 10, 2);
            $table->string('unite')->default('nuit'); // nuit, heure, jour, semaine
            $table->integer('duree_min')->default(1); // durée minimale de réservation
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->string('type_tarif')->default('standard'); // standard, weekend, saison, promo
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifs');
    }
};
