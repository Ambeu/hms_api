<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etablissements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_etablissement_id')->nullable()->constrained('type_etablissements')->nullOnDelete();
            $table->foreignId('devise_id')->nullable()->constrained('devises')->nullOnDelete();
            $table->string('nom');
            $table->string('slug')->unique();
            $table->string('adresse')->nullable();
            $table->string('telephone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->string('site_web')->nullable();
            $table->tinyInteger('nombre_etoiles')->nullable(); // 1 à 5
            $table->integer('nb_etages')->default(1);
            $table->string('image_couverture')->nullable();
            $table->string('logo_url')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('numero_registre')->nullable();
            $table->string('numero_fiscal')->nullable();
            $table->date('date_ouverture')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etablissements');
    }
};
