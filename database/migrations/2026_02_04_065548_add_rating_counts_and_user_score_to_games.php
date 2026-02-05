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
        Schema::table('games', function (Blueprint $table) {
            $table->integer('user_score')->nullable()->after('critic_score');
            $table->integer('user_score_count')->nullable()->after('user_score');
            $table->integer('critic_score_count')->nullable()->after('user_score_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['user_score', 'user_score_count', 'critic_score_count']);
        });
    }
};
