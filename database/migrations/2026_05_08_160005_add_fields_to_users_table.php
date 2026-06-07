<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // établissement actif (celui dans lequel l'utilisateur travaille au moment T)
            $table->foreignId('current_etablissement_id')->nullable()->after('id')->constrained('etablissements')->nullOnDelete();
            $table->string('nom')->nullable()->after('current_etablissement_id');
            $table->string('prenom')->nullable()->after('nom');
            // $table->string('phone')->nullable()->after('prenom');
            $table->string('role')->default('reception')->after('email');
            $table->boolean('actif')->default(true)->after('role');
            $table->timestamp('derniere_connexion')->nullable()->after('actif');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_etablissement_id']);
            $table->dropColumn(['current_etablissement_id', 'nom', 'prenom', 'phone', 'role', 'actif', 'derniere_connexion']);
        });
    }
};
