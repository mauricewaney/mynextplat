<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hard-delete any soft-deleted rows first
        DB::table('games')->whereNotNull('deleted_at')->delete();

        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->softDeletes();
        });
    }
};
