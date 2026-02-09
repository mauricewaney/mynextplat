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
        // Add 'completed' status for games that are 100% but don't have a platinum
        DB::statement("ALTER TABLE user_game MODIFY COLUMN status ENUM('backlog','in_progress','completed','platinumed','abandoned') NOT NULL DEFAULT 'backlog'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Move any 'completed' games to 'platinumed' before removing the status
        DB::table('user_game')->where('status', 'completed')->update(['status' => 'platinumed']);

        DB::statement("ALTER TABLE user_game MODIFY COLUMN status ENUM('backlog','in_progress','platinumed','abandoned') NOT NULL DEFAULT 'backlog'");
    }
};
