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
        Schema::create('trophy_guide_urls', function (Blueprint $table) {
            $table->id();
            $table->string('source'); // powerpyx, playstationtrophies
            $table->string('url')->unique();
            $table->string('extracted_slug'); // game slug extracted from URL
            $table->string('extracted_title')->nullable(); // human-readable title
            $table->foreignId('game_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('matched_at')->nullable();
            $table->timestamps();

            $table->index('source');
            $table->index('extracted_slug');
            $table->index('game_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trophy_guide_urls');
    }
};
