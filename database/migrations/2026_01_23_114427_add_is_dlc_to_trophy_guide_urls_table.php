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
        Schema::table('trophy_guide_urls', function (Blueprint $table) {
            $table->boolean('is_dlc')->default(false)->after('game_id');
            $table->index('is_dlc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trophy_guide_urls', function (Blueprint $table) {
            $table->dropIndex(['is_dlc']);
            $table->dropColumn('is_dlc');
        });
    }
};
