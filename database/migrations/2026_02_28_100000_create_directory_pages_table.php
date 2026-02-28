<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('directory_pages', function (Blueprint $table) {
            $table->id();
            $table->string('directory_type', 20);
            $table->string('slug', 100);
            $table->text('intro_text')->nullable();
            $table->json('featured_game_ids')->nullable();
            $table->json('related_pages')->nullable();
            $table->timestamps();

            $table->unique(['directory_type', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('directory_pages');
    }
};
