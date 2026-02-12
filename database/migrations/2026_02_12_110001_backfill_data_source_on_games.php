<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Priority: PT > PPX > PSNP
        // Each query only sets data_source where it's still null to avoid overwriting
        DB::table('games')
            ->whereNotNull('playstationtrophies_url')
            ->whereNull('data_source')
            ->update(['data_source' => 'playstationtrophies']);

        DB::table('games')
            ->whereNotNull('powerpyx_url')
            ->whereNull('data_source')
            ->update(['data_source' => 'powerpyx']);

        DB::table('games')
            ->whereNotNull('psnprofiles_url')
            ->whereNull('data_source')
            ->update(['data_source' => 'psnprofiles']);
    }

    public function down(): void
    {
        DB::table('games')->update(['data_source' => null]);
    }
};
