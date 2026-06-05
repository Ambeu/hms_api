<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commande_menu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commande_restaurants')->cascadeOnDelete();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->unique(['commande_id', 'menu_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commande_menu');
    }
};
