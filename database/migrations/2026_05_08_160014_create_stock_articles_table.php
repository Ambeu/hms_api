<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->unique()->constrained('article_menus')->cascadeOnDelete();
            $table->integer('quantite_disponible')->default(0);
            $table->integer('seuil_alerte')->default(5);
            $table->timestamp('mise_a_jour')->useCurrent()->useCurrentOnUpdate();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_articles');
    }
};
