<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->integer('numero');
            $table->string('nom')->nullable();
            $table->integer('nb_chambres')->default(0);
            $table->timestamps();

            $table->unique(['etablissement_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etages');
    }
};
