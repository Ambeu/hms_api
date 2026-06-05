<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ajouter etablissement_id sur les articles (pour isolation multi-établissement)
        Schema::table('article_menus', function (Blueprint $table) {
            $table->foreignId('etablissement_id')
                ->nullable()
                ->after('id')
                ->constrained('etablissements')
                ->cascadeOnDelete();
        });

        // 2. Créer la table pivot menu_article
        Schema::create('menu_article', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('article_menus')->cascadeOnDelete();
            $table->unique(['menu_id', 'article_id']);
            $table->timestamps();
        });

        // 3. Migrer les associations existantes via menu_id vers la pivot
        \Illuminate\Support\Facades\DB::statement('
            INSERT INTO menu_article (menu_id, article_id, created_at, updated_at)
            SELECT menu_id, id, NOW(), NOW()
            FROM article_menus
            WHERE menu_id IS NOT NULL
        ');

        // 4. Supprimer la colonne menu_id devenue inutile
        Schema::table('article_menus', function (Blueprint $table) {
            $table->dropForeign(['menu_id']);
            $table->dropColumn('menu_id');
        });
    }

    public function down(): void
    {
        Schema::table('article_menus', function (Blueprint $table) {
            $table->foreignId('menu_id')->nullable()->constrained('menus')->cascadeOnDelete();
        });

        Schema::dropIfExists('menu_article');

        Schema::table('article_menus', function (Blueprint $table) {
            $table->dropForeign(['etablissement_id']);
            $table->dropColumn('etablissement_id');
        });
    }
};
