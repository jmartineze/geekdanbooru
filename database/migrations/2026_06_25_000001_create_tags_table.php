<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('section', ['character', 'pose', 'outfit', 'scene']);
            $table->string('subsection', 100);
            $table->unsignedInteger('post_count')->default(0);
            $table->boolean('is_nsfw')->default(false);
            $table->timestamps();

            $table->index(['section', 'subsection']);
            $table->index('post_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
