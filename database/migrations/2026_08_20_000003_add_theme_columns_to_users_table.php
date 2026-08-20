<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('users', 'theme_mode')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('theme_mode', 10)->nullable()->after('remember_token');
            $table->string('theme_accent', 20)->nullable()->after('theme_mode');
            $table->string('theme_sidebar', 10)->nullable()->after('theme_accent');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['theme_mode', 'theme_accent', 'theme_sidebar']);
        });
    }
};
