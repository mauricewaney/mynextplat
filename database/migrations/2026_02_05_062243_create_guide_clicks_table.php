<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guide_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->enum('guide_source', ['psnprofiles', 'playstationtrophies', 'powerpyx']);
            $table->timestamp('clicked_at')->useCurrent();

            $table->index(['game_id', 'guide_source']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guide_clicks');
    }
};
