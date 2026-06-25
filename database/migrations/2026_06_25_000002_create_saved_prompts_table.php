<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_prompts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('prompt_text');
            $table->enum('section', ['full', 'character', 'pose', 'outfit', 'scene'])->default('full');
            $table->boolean('is_public')->default(false);
            $table->string('image_path')->nullable();
            $table->unsignedInteger('likes_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['is_public', 'likes_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_prompts');
    }
};
