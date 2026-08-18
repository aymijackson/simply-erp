<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A handful of tables ended up created with utf8mb4_general_ci instead of the
     * app's configured utf8mb4_unicode_ci (likely via raw SQL import on the real
     * server rather than through Laravel's schema builder), which breaks any join
     * against a matching column that IS utf8mb4_unicode_ci with "Illegal mix of
     * collations". Bring the outliers in line with the rest of the schema.
     */
    private array $columns = [
        ['table' => 'currencies', 'column' => 'code', 'definition' => 'VARCHAR(3)'],
        ['table' => 'sales_invoices', 'column' => 'currency_code', 'definition' => 'VARCHAR(3)'],
        ['table' => 'sales_orders', 'column' => 'currency_code', 'definition' => 'VARCHAR(3)'],
        ['table' => 'mfg_work_centers', 'column' => 'code', 'definition' => 'VARCHAR(255)'],
        ['table' => 'work_order_cost_types', 'column' => 'code', 'definition' => 'VARCHAR(255)'],
    ];

    public function up(): void
    {
        $hadForeignKey = $this->hasForeignKey('sales_orders', 'fk_so_currency');

        if ($hadForeignKey) {
            DB::statement("ALTER TABLE sales_orders DROP FOREIGN KEY fk_so_currency");
        }

        foreach ($this->columns as $c) {
            if (!Schema::hasTable($c['table']) || !Schema::hasColumn($c['table'], $c['column'])) {
                continue;
            }

            $collation = DB::selectOne("
                SELECT COLLATION_NAME AS collation, IS_NULLABLE AS is_nullable
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
            ", [$c['table'], $c['column']]);

            if (!$collation || $collation->collation === 'utf8mb4_unicode_ci') {
                continue;
            }

            $null = $collation->is_nullable === 'YES' ? 'NULL' : 'NOT NULL';

            DB::statement("ALTER TABLE `{$c['table']}` MODIFY `{$c['column']}` {$c['definition']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci {$null}");
        }

        if ($hadForeignKey) {
            DB::statement("ALTER TABLE sales_orders ADD CONSTRAINT fk_so_currency FOREIGN KEY (currency_code) REFERENCES currencies (code)");
        }
    }

    public function down(): void
    {
        // Intentionally no-op: reverting to a mismatched collation would just reintroduce the bug.
    }

    private function hasForeignKey(string $table, string $constraint): bool
    {
        $row = DB::selectOne("
            SELECT 1 AS found
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$table, $constraint]);

        return (bool) $row;
    }
};
