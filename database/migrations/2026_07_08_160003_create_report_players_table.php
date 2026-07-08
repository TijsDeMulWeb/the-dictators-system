<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->string('country')->nullable();
            $table->integer('points')->default(0);
            $table->timestamps();

            $table->unique(['report_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_players');
    }
};
