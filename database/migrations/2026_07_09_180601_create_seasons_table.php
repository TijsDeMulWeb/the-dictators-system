<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('base')->unique();
            $table->unsignedInteger('next_number');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        // Seed the current season (500–599), continuing at game 576.
        DB::table('seasons')->insert([
            'base' => 500,
            'next_number' => 576,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
