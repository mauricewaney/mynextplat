<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Games table — boolean filter columns missing indexes
        Schema::table('games', function (Blueprint $table) {
            $table->index('has_platinum');
            $table->index('is_unobtainable');
            $table->index('is_verified');
            $table->index('user_score_count');
            $table->index('critic_score_count');
        });

        // Pivot tables — add explicit reverse-lookup indexes
        // MySQL auto-creates FK indexes, but these ensure optimal
        // whereHas() subquery performance regardless of engine behavior
        Schema::table('game_genre', function (Blueprint $table) {
            $table->index('genre_id');
        });

        Schema::table('game_tag', function (Blueprint $table) {
            $table->index('tag_id');
        });

        Schema::table('game_platform', function (Blueprint $table) {
            $table->index('platform_id');
        });

        // User game table — status is filtered on, game_id used in recommendations
        Schema::table('user_game', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropIndex(['has_platinum']);
            $table->dropIndex(['is_unobtainable']);
            $table->dropIndex(['is_verified']);
            $table->dropIndex(['user_score_count']);
            $table->dropIndex(['critic_score_count']);
        });

        Schema::table('game_genre', function (Blueprint $table) {
            $table->dropIndex(['genre_id']);
        });

        Schema::table('game_tag', function (Blueprint $table) {
            $table->dropIndex(['tag_id']);
        });

        Schema::table('game_platform', function (Blueprint $table) {
            $table->dropIndex(['platform_id']);
        });

        Schema::table('user_game', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};
