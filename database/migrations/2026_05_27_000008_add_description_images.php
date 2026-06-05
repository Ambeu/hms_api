<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chambre : description
        Schema::table('chambres', function (Blueprint $table) {
            $table->text('description')->nullable()->after('equipements');
        });

        // Table des images de chambre (0,n)
        Schema::create('chambre_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chambre_id')->constrained('chambres')->cascadeOnDelete();
            $table->string('url');
            $table->integer('ordre')->default(0);
            $table->boolean('principale')->default(false);
            $table->timestamps();
        });

        // Menu : description + image
        Schema::table('menus', function (Blueprint $table) {
            $table->text('description')->nullable()->after('nom');
            $table->string('image_url')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chambre_images');

        Schema::table('chambres', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn(['description', 'image_url']);
        });
    }
};
