<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarifs', function (Blueprint $table) {
            $table->string('unite')->default('nuit')->after('prix');    // nuit, heure, jour, semaine
            $table->integer('duree_min')->default(1)->after('unite');   // durée minimale en unités
        });
    }

    public function down(): void
    {
        Schema::table('tarifs', function (Blueprint $table) {
            $table->dropColumn(['unite', 'duree_min']);
        });
    }
};
