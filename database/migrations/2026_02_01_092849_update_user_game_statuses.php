<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, modify the enum to include all values (old and new)
        DB::statement("ALTER TABLE user_game MODIFY COLUMN status ENUM('want_to_play','playing','completed','platinum','abandoned','backlog','in_progress','platinumed') NOT NULL DEFAULT 'backlog'");

        // Update old status values to new ones
        DB::table('user_game')->where('status', 'want_to_play')->update(['status' => 'backlog']);
        DB::table('user_game')->where('status', 'playing')->update(['status' => 'in_progress']);
        DB::table('user_game')->where('status', 'completed')->update(['status' => 'platinumed']);
        DB::table('user_game')->where('status', 'platinum')->update(['status' => 'platinumed']);

        // Now restrict to only new values
        DB::statement("ALTER TABLE user_game MODIFY COLUMN status ENUM('backlog','in_progress','platinumed','abandoned') NOT NULL DEFAULT 'backlog'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, modify the enum to include all values
        DB::statement("ALTER TABLE user_game MODIFY COLUMN status ENUM('want_to_play','playing','completed','platinum','abandoned','backlog','in_progress','platinumed') NOT NULL DEFAULT 'want_to_play'");

        // Revert to old status values
        DB::table('user_game')->where('status', 'backlog')->update(['status' => 'want_to_play']);
        DB::table('user_game')->where('status', 'in_progress')->update(['status' => 'playing']);
        DB::table('user_game')->where('status', 'platinumed')->update(['status' => 'platinum']);

        // Restrict to only old values
        DB::statement("ALTER TABLE user_game MODIFY COLUMN status ENUM('want_to_play','playing','completed','platinum','abandoned') NOT NULL DEFAULT 'want_to_play'");
    }
};
