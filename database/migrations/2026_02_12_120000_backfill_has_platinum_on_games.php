<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Set has_platinum = true for all games that have guides
        // Most games with guides have a platinum trophy
        DB::table('games')
            ->where(function ($q) {
                $q->whereNotNull('psnprofiles_url')
                  ->orWhereNotNull('playstationtrophies_url')
                  ->orWhereNotNull('powerpyx_url');
            })
            ->update(['has_platinum' => true]);
    }

    public function down(): void
    {
        // Reset all has_platinum to false except the 62 that were synced from PSN titles
        // Can't perfectly reverse this, so we reset games that don't have platinum_count > 0
        DB::table('games')
            ->where('has_platinum', true)
            ->where(function ($q) {
                $q->whereNull('platinum_count')
                  ->orWhere('platinum_count', 0);
            })
            ->update(['has_platinum' => false]);
    }
};
