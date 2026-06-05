<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etablissement_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('role')->default('reception'); // rôle spécifique à cet établissement
            $table->timestamps();

            $table->unique(['user_id', 'etablissement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etablissement_user');
    }
};
