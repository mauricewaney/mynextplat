<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->boolean('has_guide')->default(false)->after('powerpyx_url');
            $table->index('has_guide');
        });

        // Backfill: set has_guide = true where any guide URL exists
        DB::table('games')
            ->where(function ($q) {
                $q->whereNotNull('psnprofiles_url')
                  ->orWhereNotNull('playstationtrophies_url')
                  ->orWhereNotNull('powerpyx_url');
            })
            ->update(['has_guide' => true]);
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('has_guide');
        });
    }
};
