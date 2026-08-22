<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * stock_entries.purchase_order_id and .purchase_requisition_id were foreign-
 * keyed to purchase_order_headers / purchase_requisition_headers - dead,
 * empty legacy tables from before the Procurement module was rebuilt onto
 * proc_purchase_orders / proc_purchase_requisitions. Nothing in the app
 * writes to or reads from the *_headers tables anymore (confirmed via a
 * full grep - only their own migrations reference them), but the FK still
 * pointed there, so any real purchase order/requisition id (which only
 * ever exists in the proc_ tables) failed the constraint the moment
 * something tried to post a stock entry against it - e.g. posting a goods
 * receipt.
 */
return new class extends Migration {
    private function hasForeign(string $name): bool
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'stock_entries')
            ->where('CONSTRAINT_NAME', $name)
            ->exists();
    }

    public function up(): void
    {
        if ($this->hasForeign('fk_se_po')) {
            Schema::table('stock_entries', fn (Blueprint $table) => $table->dropForeign('fk_se_po'));
        }
        if ($this->hasForeign('fk_se_pr')) {
            Schema::table('stock_entries', fn (Blueprint $table) => $table->dropForeign('fk_se_pr'));
        }

        Schema::table('stock_entries', function (Blueprint $table) {
            $table->foreign('purchase_order_id', 'fk_se_po')
                ->references('id')->on('proc_purchase_orders')->nullOnDelete();
            $table->foreign('purchase_requisition_id', 'fk_se_pr')
                ->references('id')->on('proc_purchase_requisitions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if ($this->hasForeign('fk_se_po')) {
            Schema::table('stock_entries', fn (Blueprint $table) => $table->dropForeign('fk_se_po'));
        }
        if ($this->hasForeign('fk_se_pr')) {
            Schema::table('stock_entries', fn (Blueprint $table) => $table->dropForeign('fk_se_pr'));
        }

        Schema::table('stock_entries', function (Blueprint $table) {
            $table->foreign('purchase_order_id', 'fk_se_po')
                ->references('id')->on('purchase_order_headers')->nullOnDelete();
            $table->foreign('purchase_requisition_id', 'fk_se_pr')
                ->references('id')->on('purchase_requisition_headers')->nullOnDelete();
        });
    }
};
