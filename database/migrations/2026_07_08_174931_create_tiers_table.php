<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('points');
            $table->timestamps();
        });

        $now = now();
        DB::table('tiers')->insert([
            ['name' => 'protector', 'points' => 800, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'gold', 'points' => 500, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'silver', 'points' => 400, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'bronze', 'points' => 300, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'mikkim', 'points' => 250, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'brown', 'points' => 200, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tiers');
    }
};
