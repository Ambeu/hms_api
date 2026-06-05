<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('chambre_id')->constrained('chambres')->restrictOnDelete();
            $table->foreignId('tarif_id')->nullable()->constrained('tarifs')->nullOnDelete();
            $table->string('canal_source')->nullable();
            $table->string('statut')->default('confirmee'); // en_attente, confirmee, annulee, no_show, terminee
            $table->string('unite')->default('nuit'); // nuit, heure, jour, semaine (copié du tarif)
            $table->dateTime('date_arrivee');
            $table->dateTime('date_depart');
            $table->integer('nb_adultes')->default(1);
            $table->integer('nb_enfants')->default(0);
            $table->decimal('prix_total', 10, 2)->default(0);
            $table->string('code_confirmation')->unique()->nullable();
            $table->text('notes_speciales')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
