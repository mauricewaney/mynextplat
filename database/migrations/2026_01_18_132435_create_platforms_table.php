<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platforms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // "PlayStation 5", "PlayStation 4", etc.
            $table->string('slug')->unique(); // "ps5", "ps4", etc.
            $table->string('short_name')->nullable(); // "PS5", "PS4" for display
            $table->timestamps();

            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platforms');
    }
};
