<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Supprimer image_url scalar (remplacé par la table)
        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('image_url');
        });

        Schema::create('menu_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->string('url');
            $table->integer('ordre')->default(0);
            $table->boolean('principale')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_images');

        Schema::table('menus', function (Blueprint $table) {
            $table->string('image_url')->nullable()->after('description');
        });
    }
};
