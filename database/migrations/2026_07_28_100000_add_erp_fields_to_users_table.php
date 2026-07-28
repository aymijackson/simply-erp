<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'employee_id')) {
                $table->foreignId('employee_id')->nullable()->after('company_id')->constrained('employees')->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'can_access_erp')) {
                $table->boolean('can_access_erp')->default(true)->after('password');
            }
            if (! Schema::hasColumn('users', 'can_access_admin')) {
                $table->boolean('can_access_admin')->default(false)->after('can_access_erp');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['company_id', 'employee_id', 'can_access_erp', 'can_access_admin'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    if (in_array($col, ['company_id', 'employee_id'], true)) {
                        $table->dropConstrainedForeignId($col);
                    } else {
                        $table->dropColumn($col);
                    }
                }
            }
        });
    }
};
