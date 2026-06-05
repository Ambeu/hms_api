<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programme_fidelites', function (Blueprint $table) {
            $table->id();
            $table->string('niveau');
            $table->integer('points_min');
            $table->integer('points_max')->nullable();
            $table->decimal('remise_pct', 5, 2)->default(0);
            $table->json('avantages')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programme_fidelites');
    }
};
