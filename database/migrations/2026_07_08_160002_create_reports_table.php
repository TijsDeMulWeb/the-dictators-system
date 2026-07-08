<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('report_number')->unique();
            $table->string('game')->default('Asia');
            $table->unsignedInteger('day')->nullable();
            $table->foreignId('leader_id')->constrained('players')->cascadeOnDelete();
            $table->enum('result', ['win', 'loss']);
            $table->string('ingame_screenshot_path')->nullable();
            $table->string('report_image_path')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('review_message_id')->nullable();
            $table->string('posted_message_id')->nullable();
            $table->string('reviewed_by_discord_id')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
