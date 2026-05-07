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
        \DB::table('user_game')
            ->whereIn('game_id', function ($query) {
                $query->select('id')
                    ->from('games')
                    ->whereNotNull('psnprofiles_url')
                    ->orWhereNotNull('playstationtrophies_url')
                    ->orWhereNotNull('powerpyx_url');
            })
            ->update(['guide_notified_at' => now()]);
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
