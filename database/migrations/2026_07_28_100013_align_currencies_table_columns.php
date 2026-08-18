<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The real currencies table (pre-existing, our create-table migration was skipped
     * via the hasTable guard) only has code/description/timestamps, but every currency
     * lookup across Finance (BankAccountsController, JournalEntriesController,
     * LookupsController, ExpensesController, SupplierCreditsController) selects
     * `name` and filters `is_active`. Align the real table instead of rewriting five
     * controllers - `description` already holds exactly what the code expects as `name`.
     */
    public function up(): void
    {
        if (!Schema::hasTable('currencies')) {
            return;
        }

        if (Schema::hasColumn('currencies', 'description') && !Schema::hasColumn('currencies', 'name')) {
            Schema::table('currencies', function (Blueprint $table) {
                $table->renameColumn('description', 'name');
            });
        }

        if (!Schema::hasColumn('currencies', 'symbol')) {
            Schema::table('currencies', function (Blueprint $table) {
                $table->string('symbol')->nullable()->after('name');
            });
        }

        if (!Schema::hasColumn('currencies', 'is_active')) {
            Schema::table('currencies', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('symbol');
            });

            DB::table('currencies')->update(['is_active' => true]);
        }
    }

    public function down(): void
    {
        // Intentionally no-op: this reconciles real data drift, reverting would just reintroduce the bug.
    }
};
