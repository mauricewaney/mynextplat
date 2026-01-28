<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('category', [
                'trophy_data',      // difficulty, time, playthroughs, missables, online
                'game_info',        // title, developer, publisher, release date
                'guide_links',      // wrong/dead URL, suggest new guide
                'images',           // wrong cover/banner
                'other'
            ]);
            $table->text('description');
            $table->string('source_url', 500)->nullable(); // proof/reference URL
            $table->enum('status', ['pending', 'in_review', 'applied', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('email')->nullable(); // for guest contact (optional)
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes for common queries
            $table->index('status');
            $table->index('category');
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_corrections');
    }
};
