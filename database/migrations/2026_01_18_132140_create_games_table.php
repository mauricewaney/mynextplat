<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();

            // Basic info
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('developer')->nullable();
            $table->string('publisher')->nullable();
            $table->date('release_date')->nullable();

            // Trophy data
            $table->integer('difficulty')->nullable(); // 1-10
            $table->integer('time_min')->nullable(); // hours
            $table->integer('time_max')->nullable(); // hours
            $table->integer('playthroughs_required')->nullable();
            $table->boolean('has_online_trophies')->default(false);
            $table->boolean('missable_trophies')->default(false);

            // Scores
            $table->integer('critic_score')->nullable();
            $table->integer('opencritic_score')->nullable();

            // Pricing
            $table->decimal('base_price', 8, 2)->nullable();
            $table->decimal('psplus_price', 8, 2)->nullable();
            $table->decimal('current_discount_price', 8, 2)->nullable();
            $table->boolean('is_psplus_extra')->default(false);
            $table->boolean('is_psplus_premium')->default(false);

            // Images & Links
            $table->string('cover_url')->nullable();
            $table->string('banner_url')->nullable();
            $table->string('psnprofiles_url')->nullable();
            $table->string('playstationtrophies_url')->nullable();
            $table->string('powerpyx_url')->nullable();

            // Metadata
            $table->timestamp('last_scraped_at')->nullable();
            $table->boolean('needs_review')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('slug');
            $table->index('developer');
            $table->index('publisher');
            $table->index('difficulty');
            $table->index('needs_review');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
