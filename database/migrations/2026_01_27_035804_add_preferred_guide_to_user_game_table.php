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
            $table->enum('preferred_guide', ['psnprofiles', 'playstationtrophies', 'powerpyx'])
                ->nullable()
                ->after('guide_notified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_game', function (Blueprint $table) {
            $table->dropColumn('preferred_guide');
        });
    }
};
