<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The work_orders.status column was created with enum('pending','in_progress','completed','cancelled'),
 * but WorkOrderService (release/start/complete/close) and every work-order view/badge in the app
 * operate on draft/released/in_progress/paused/completed/closed. That mismatch means release() and
 * close() always failed: new work orders were created as 'pending', release() only accepts 'draft',
 * and 'closed' isn't a valid enum value at all. This aligns the column with what the app actually uses.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('work_orders')) {
            return;
        }

        // MySQL remaps existing enum values by name when narrowing the list; a value not present
        // in the new list (like the existing 'pending' rows) gets silently truncated to ''. Widen
        // the enum to the union of old and new values first, migrate the data, then narrow it.
        DB::statement("ALTER TABLE work_orders MODIFY status ENUM('pending','draft','released','in_progress','paused','completed','closed','cancelled') NOT NULL DEFAULT 'draft'");

        DB::table('work_orders')->where('status', 'pending')->update(['status' => 'draft']);

        DB::statement("ALTER TABLE work_orders MODIFY status ENUM('draft','released','in_progress','paused','completed','closed','cancelled') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        if (!Schema::hasTable('work_orders')) {
            return;
        }

        DB::table('work_orders')->where('status', 'draft')->update(['status' => 'pending']);
        DB::table('work_orders')->whereIn('status', ['released', 'paused', 'closed'])->update(['status' => 'pending']);

        DB::statement("ALTER TABLE work_orders MODIFY status ENUM('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending'");
    }
};
