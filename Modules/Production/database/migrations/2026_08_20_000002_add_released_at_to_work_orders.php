<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// WorkOrderService::release() has always written released_at, but the column was
// never migrated - every release() call failed with "Unknown column 'released_at'".
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('work_orders') && !Schema::hasColumn('work_orders', 'released_at')) {
            Schema::table('work_orders', function (Blueprint $table) {
                $table->timestamp('released_at')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('work_orders') && Schema::hasColumn('work_orders', 'released_at')) {
            Schema::table('work_orders', function (Blueprint $table) {
                $table->dropColumn('released_at');
            });
        }
    }
};
