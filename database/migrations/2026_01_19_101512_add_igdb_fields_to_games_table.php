<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // IGDB tracking
            $table->unsignedBigInteger('igdb_id')->nullable()->unique()->after('id');

            // Time to beat (in hours)
            $table->decimal('time_to_beat_main', 6, 1)->nullable()->after('time_max');
            $table->decimal('time_to_beat_extra', 6, 1)->nullable()->after('time_to_beat_main');
            $table->decimal('time_to_beat_completionist', 6, 1)->nullable()->after('time_to_beat_extra');

            $table->index('igdb_id');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropIndex(['igdb_id']);
            $table->dropColumn([
                'igdb_id',
                'time_to_beat_main',
                'time_to_beat_extra',
                'time_to_beat_completionist',
            ]);
        });
    }
};
