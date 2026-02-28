<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('directory_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('directory_page_id')->constrained('directory_pages')->cascadeOnDelete();
            $table->string('title', 200);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('filter_definition')->nullable();
            $table->json('game_ids')->nullable();
            $table->unsignedTinyInteger('limit')->default(6);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('directory_sections');
    }
};
