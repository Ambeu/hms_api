<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avis_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->tinyInteger('note_globale');
            $table->tinyInteger('note_chambre')->nullable();
            $table->tinyInteger('note_service')->nullable();
            $table->tinyInteger('note_restauration')->nullable();
            $table->text('commentaire')->nullable();
            $table->timestamps();

            $table->unique(['client_id', 'reservation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avis_clients');
    }
};
