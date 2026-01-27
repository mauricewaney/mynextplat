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
        Schema::table('user_game', function (Blueprint $table) {
            // Track if user was notified about a guide for this game
            $table->timestamp('guide_notified_at')->nullable()->after('notes');
        });

        // Mark existing entries with guides as already notified (don't spam existing users)
        \DB::statement("
            UPDATE user_game ug
            JOIN games g ON ug.game_id = g.id
            SET ug.guide_notified_at = NOW()
            WHERE g.psnprofiles_url IS NOT NULL
               OR g.playstationtrophies_url IS NOT NULL
               OR g.powerpyx_url IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_game', function (Blueprint $table) {
            $table->dropColumn('guide_notified_at');
        });
    }
};
