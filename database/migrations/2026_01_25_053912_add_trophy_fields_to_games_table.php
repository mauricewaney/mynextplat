<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // Trophy counts (synced from linked PSN titles)
            $table->unsignedSmallInteger('bronze_count')->nullable()->after('missable_trophies');
            $table->unsignedSmallInteger('silver_count')->nullable()->after('bronze_count');
            $table->unsignedSmallInteger('gold_count')->nullable()->after('silver_count');
            $table->unsignedSmallInteger('platinum_count')->default(0)->after('gold_count'); // Usually 0 or 1
            $table->boolean('has_platinum')->default(false)->after('platinum_count');

            // Fallback icon from PSN (used if no cover_url)
            $table->string('trophy_icon_url', 500)->nullable()->after('banner_url');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn([
                'bronze_count',
                'silver_count',
                'gold_count',
                'platinum_count',
                'has_platinum',
                'trophy_icon_url',
            ]);
        });
    }
};
