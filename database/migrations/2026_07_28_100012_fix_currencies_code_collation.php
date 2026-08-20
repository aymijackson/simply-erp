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
     *
     * currencies.code in particular is a lookup key that other tables' currency_code
     * columns reference by FK. Which tables do that varies between environments (the
     * dev mirror doesn't have every FK the real server has), so rather than hardcode
     * one known constraint, every FK referencing an altered column is discovered from
     * information_schema, dropped, and recreated with its original definition - and
     * the referencing (child) column is brought to the same collation too, since MySQL
     * refuses to form a FK between columns whose collations no longer match, which is
     * exactly what happens to every column referencing currencies.code once code itself
     * changes collation but the child column doesn't.
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
        $droppedForeignKeys = [];

        foreach ($this->columns as $c) {
            if (!Schema::hasTable($c['table']) || !Schema::hasColumn($c['table'], $c['column'])) {
                continue;
            }

            foreach ($this->foreignKeysReferencing($c['table'], $c['column']) as $fk) {
                DB::statement("ALTER TABLE `{$fk->TABLE_NAME}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                $droppedForeignKeys[] = $fk;
            }
        }

        foreach ($this->columns as $c) {
            $this->alterColumnCollation($c['table'], $c['column'], $c['definition']);
        }

        // The child side of every FK we just dropped also has to match currencies.code's
        // new collation, or MySQL refuses to recreate the constraint (errno 150).
        foreach ($droppedForeignKeys as $fk) {
            $this->alterColumnCollation($fk->TABLE_NAME, $fk->COLUMN_NAME);
        }

        foreach ($droppedForeignKeys as $fk) {
            $onUpdate = $fk->UPDATE_RULE && $fk->UPDATE_RULE !== 'RESTRICT' ? " ON UPDATE {$fk->UPDATE_RULE}" : '';
            $onDelete = $fk->DELETE_RULE && $fk->DELETE_RULE !== 'RESTRICT' ? " ON DELETE {$fk->DELETE_RULE}" : '';

            DB::statement("
                ALTER TABLE `{$fk->TABLE_NAME}`
                ADD CONSTRAINT `{$fk->CONSTRAINT_NAME}`
                FOREIGN KEY (`{$fk->COLUMN_NAME}`)
                REFERENCES `{$fk->REFERENCED_TABLE_NAME}` (`{$fk->REFERENCED_COLUMN_NAME}`)
                {$onUpdate}{$onDelete}
            ");
        }
    }

    public function down(): void
    {
        // Intentionally no-op: reverting to a mismatched collation would just reintroduce the bug.
    }

    /**
     * Bring a single column to utf8mb4_unicode_ci if it isn't already. $definition is used
     * when known (the 5 columns this migration targets by name); for columns discovered
     * dynamically via a FK relationship, it's reconstructed from information_schema instead.
     */
    private function alterColumnCollation(string $table, string $column, ?string $definition = null): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        $col = DB::selectOne("
            SELECT DATA_TYPE AS data_type, CHARACTER_MAXIMUM_LENGTH AS max_length,
                   IS_NULLABLE AS is_nullable, COLLATION_NAME AS collation
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ", [$table, $column]);

        if (!$col || $col->collation === 'utf8mb4_unicode_ci') {
            return;
        }

        $definition ??= $col->max_length
            ? strtoupper($col->data_type).'('.$col->max_length.')'
            : strtoupper($col->data_type);

        $null = $col->is_nullable === 'YES' ? 'NULL' : 'NOT NULL';

        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` {$definition} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci {$null}");
    }

    /** All FK constraints (from any table) that reference $table.$column, with their ON UPDATE/DELETE rules. */
    private function foreignKeysReferencing(string $table, string $column): array
    {
        return DB::select("
            SELECT
                kcu.CONSTRAINT_NAME, kcu.TABLE_NAME, kcu.COLUMN_NAME,
                kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME,
                rc.UPDATE_RULE, rc.DELETE_RULE
            FROM information_schema.KEY_COLUMN_USAGE kcu
            JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                ON rc.CONSTRAINT_SCHEMA = kcu.TABLE_SCHEMA AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
            WHERE kcu.TABLE_SCHEMA = DATABASE()
                AND kcu.REFERENCED_TABLE_NAME = ?
                AND kcu.REFERENCED_COLUMN_NAME = ?
        ", [$table, $column]);
    }
};
