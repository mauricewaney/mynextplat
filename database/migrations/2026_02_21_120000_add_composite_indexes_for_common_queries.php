<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // Composite indexes for has_guide + common sort columns
            // These let MySQL filter and sort using a single index scan
            $table->index(['has_guide', 'critic_score'], 'idx_guide_critic');
            $table->index(['has_guide', 'user_score'], 'idx_guide_user');
            $table->index(['has_guide', 'release_date'], 'idx_guide_release');
            $table->index(['has_guide', 'created_at'], 'idx_guide_created');
            $table->index(['has_guide', 'difficulty'], 'idx_guide_difficulty');
            $table->index(['has_guide', 'time_min'], 'idx_guide_time');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropIndex('idx_guide_critic');
            $table->dropIndex('idx_guide_user');
            $table->dropIndex('idx_guide_release');
            $table->dropIndex('idx_guide_created');
            $table->dropIndex('idx_guide_difficulty');
            $table->dropIndex('idx_guide_time');
        });
    }
};
