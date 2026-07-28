<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('employees', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
            if (Schema::hasColumn('employees', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
