<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('challenge_id')->nullable()->after('day')->constrained('challenges')->nullOnDelete();
            $table->unsignedInteger('challenge_bonus')->nullable()->after('challenge_id');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('challenge_id');
            $table->dropColumn('challenge_bonus');
        });
    }
};
