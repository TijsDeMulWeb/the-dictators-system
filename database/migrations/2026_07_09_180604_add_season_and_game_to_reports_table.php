<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('season_id')->nullable()->after('report_number')->constrained('seasons')->nullOnDelete();
            $table->foreignId('game_id')->nullable()->after('season_id')->constrained('games')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('season_id');
            $table->dropConstrainedForeignId('game_id');
        });
    }
};
