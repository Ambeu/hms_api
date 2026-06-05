<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chambres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('type_chambre_id')->constrained('type_chambres')->restrictOnDelete();
            $table->foreignId('etage_id')->constrained('etages')->restrictOnDelete();
            $table->string('numero');
            $table->string('statut')->default('disponible'); // disponible, occupee, en_nettoyage, hors_service
            $table->string('vue')->nullable();
            $table->boolean('hors_service')->default(false);
            $table->json('equipements')->nullable();
            $table->timestamps();

            $table->unique(['etablissement_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chambres');
    }
};
