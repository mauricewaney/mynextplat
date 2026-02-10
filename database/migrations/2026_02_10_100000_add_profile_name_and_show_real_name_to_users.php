<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_name')->nullable()->after('profile_slug');
        });

        // Populate profile_name from existing profile_slug values (de-slugify)
        // and re-generate profile_slug from profile_name
        $users = \App\Models\User::whereNotNull('profile_slug')->get();

        foreach ($users as $user) {
            $profileName = Str::title(str_replace('-', ' ', $user->profile_slug));
            $user->profile_name = $profileName;
            $user->profile_slug = Str::slug($profileName);
            $user->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['profile_name']);
        });
    }
};
