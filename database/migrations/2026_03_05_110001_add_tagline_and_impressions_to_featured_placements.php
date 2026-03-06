<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('featured_placements', function (Blueprint $table) {
            $table->string('tagline', 120)->nullable()->after('label');
            $table->unsignedInteger('impressions')->default(0)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('featured_placements', function (Blueprint $table) {
            $table->dropColumn(['tagline', 'impressions']);
        });
    }
};
