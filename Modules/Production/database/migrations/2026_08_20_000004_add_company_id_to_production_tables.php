<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Production had no company scoping at all - every BOM, routing, raw material and work
// order was visible/editable by every company. Adds company_id to the module's top-level
// (non-child) tables, matching how Finance's transactional tables are scoped, and backfills
// existing rows to company 1 (the same fallback used throughout the app for legacy data).
return new class extends Migration
{
    protected array $tables = ['bom_headers', 'routings', 'production_raw_materials', 'work_orders'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table) || Schema::hasColumn($table, 'company_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
            });

            DB::table($table)->whereNull('company_id')->update(['company_id' => 1]);
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'company_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('company_id');
            });
        }
    }
};
