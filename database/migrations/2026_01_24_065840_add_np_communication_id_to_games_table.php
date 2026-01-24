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
            // PSN's unique identifier for trophy sets (e.g., "NPWR24886_00")
            // A game can have multiple NP IDs (PS4/PS5 versions), stored as JSON array
            $table->json('np_communication_ids')->nullable()->after('igdb_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('np_communication_ids');
        });
    }
};
