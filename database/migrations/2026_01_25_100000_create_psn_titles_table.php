<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('psn_titles', function (Blueprint $table) {
            $table->id();

            // PSN identifiers
            $table->string('np_communication_id', 20)->unique(); // e.g., "NPWR24886_00"
            $table->string('psn_title'); // Title name from PSN
            $table->string('platform', 20)->nullable(); // PS4, PS5, PS3, Vita
            $table->string('icon_url', 500)->nullable(); // Trophy icon from PSN

            // Link to local game (null = unmatched)
            $table->foreignId('game_id')->nullable()->constrained()->nullOnDelete();

            // Discovery tracking
            $table->string('discovered_from')->nullable(); // PSN username that first had this
            $table->unsignedInteger('times_seen')->default(1); // How many users have this

            // Trophy info (useful for matching)
            $table->unsignedSmallInteger('bronze_count')->nullable();
            $table->unsignedSmallInteger('silver_count')->nullable();
            $table->unsignedSmallInteger('gold_count')->nullable();
            $table->boolean('has_platinum')->default(false);

            $table->timestamps();

            // Indexes
            $table->index('game_id');
            $table->index('psn_title');
            $table->index('platform');
            $table->index('times_seen');
            $table->index(['game_id', 'times_seen']); // For sorting unmatched by popularity
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('psn_titles');
    }
};
