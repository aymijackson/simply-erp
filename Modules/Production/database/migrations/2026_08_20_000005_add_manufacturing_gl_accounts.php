<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Manufacturing had no GL integration at all - material issue/return only incremented a
// counter column, and completing a Work Order never posted anything. This adds the account
// mapping columns (alongside the existing ar/ap/cogs/inventory ones) and a per-line cost
// snapshot on work_order_materials so WorkOrderPostingService has somewhere to read/write.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('finance_account_mappings') && !Schema::hasColumn('finance_account_mappings', 'wip_account_id')) {
            Schema::table('finance_account_mappings', function (Blueprint $t) {
                $t->foreignId('wip_account_id')->nullable()->after('inventory_asset_account_id')->constrained('finance_accounts')->nullOnDelete();
                $t->foreignId('finished_goods_account_id')->nullable()->after('wip_account_id')->constrained('finance_accounts')->nullOnDelete();
            });
        }

        if (Schema::hasTable('work_order_materials') && !Schema::hasColumn('work_order_materials', 'unit_cost')) {
            Schema::table('work_order_materials', function (Blueprint $t) {
                $t->decimal('unit_cost', 15, 4)->nullable()->after('product_variant_id');
            });
        }

        if (Schema::hasTable('work_orders') && !Schema::hasColumn('work_orders', 'completion_journal_entry_id')) {
            Schema::table('work_orders', function (Blueprint $t) {
                $t->foreignId('completion_journal_entry_id')->nullable()->after('status')->constrained('finance_journal_entries')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('finance_account_mappings') && Schema::hasColumn('finance_account_mappings', 'wip_account_id')) {
            Schema::table('finance_account_mappings', function (Blueprint $t) {
                $t->dropConstrainedForeignId('finished_goods_account_id');
                $t->dropConstrainedForeignId('wip_account_id');
            });
        }

        if (Schema::hasTable('work_order_materials') && Schema::hasColumn('work_order_materials', 'unit_cost')) {
            Schema::table('work_order_materials', function (Blueprint $t) {
                $t->dropColumn('unit_cost');
            });
        }

        if (Schema::hasTable('work_orders') && Schema::hasColumn('work_orders', 'completion_journal_entry_id')) {
            Schema::table('work_orders', function (Blueprint $t) {
                $t->dropConstrainedForeignId('completion_journal_entry_id');
            });
        }
    }
};
