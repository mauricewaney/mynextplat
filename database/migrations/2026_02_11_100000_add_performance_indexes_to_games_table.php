<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // Sort columns — used in ORDER BY, currently cause full table scans
            $table->index('critic_score');
            $table->index('user_score');
            $table->index('time_min');
            $table->index('release_date');
            $table->index('created_at');
            $table->index('playthroughs_required');

            // Filter columns — used in WHERE clauses
            $table->index('time_max');
            $table->index('has_online_trophies');
            $table->index('missable_trophies');

            // Guide URL columns — used by has_guide filter (IS NOT NULL checks)
            $table->index('psnprofiles_url');
            $table->index('playstationtrophies_url');
            $table->index('powerpyx_url');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropIndex(['critic_score']);
            $table->dropIndex(['user_score']);
            $table->dropIndex(['time_min']);
            $table->dropIndex(['release_date']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['playthroughs_required']);
            $table->dropIndex(['time_max']);
            $table->dropIndex(['has_online_trophies']);
            $table->dropIndex(['missable_trophies']);
            $table->dropIndex(['psnprofiles_url']);
            $table->dropIndex(['playstationtrophies_url']);
            $table->dropIndex(['powerpyx_url']);
        });
    }
};
