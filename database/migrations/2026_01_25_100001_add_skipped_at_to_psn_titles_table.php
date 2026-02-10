<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('psn_titles', 'skipped_at')) {
            return;
        }

        Schema::table('psn_titles', function (Blueprint $table) {
            $table->timestamp('skipped_at')->nullable()->after('times_seen');
            $table->index('skipped_at');
        });
    }

    public function down(): void
    {
        Schema::table('psn_titles', function (Blueprint $table) {
            $table->dropColumn('skipped_at');
        });
    }
};
