<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The earlier 2026_07_28_100012 migration only chased currencies.code and its known
 * FK-linked columns, but the real server has utf8mb4_general_ci drift scattered across
 * dozens of columns that were never linked by an actual FK constraint - e.g.
 * finance_bank_accounts.currency_code is joined to currencies.code in plain SQL with no
 * FK at all, so it silently keeps causing "Illegal mix of collations" / DataTables ajax
 * errors on whatever page touches it next. Rather than patch these one table at a time
 * as each one gets reported, this scans every base table in the schema for a column
 * whose collation isn't utf8mb4_unicode_ci and fixes all of them in one pass.
 *
 * Deliberately excluded:
 *  - countries_old: different charset entirely (latin1) and an explicitly deprecated
 *    table by name - a charset conversion is a different, riskier problem than the
 *    collation drift this migration targets.
 *  - Any *_bin collation (audit_logs.meta, *_audits.old_values/new_values, raw_payload):
 *    binary collations on JSON/audit-diff columns look intentional (exact byte
 *    comparison), not drift, so they're left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        $targets = $this->targetColumns();
        if (empty($targets)) {
            return;
        }

        $foreignKeys = $this->affectedForeignKeys($targets);

        foreach ($foreignKeys as $fk) {
            DB::statement("ALTER TABLE `{$fk->TABLE_NAME}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        foreach ($targets as $col) {
            $null = $col->IS_NULLABLE === 'YES' ? 'NULL' : 'NOT NULL';

            DB::statement("
                ALTER TABLE `{$col->TABLE_NAME}`
                MODIFY `{$col->COLUMN_NAME}` {$col->COLUMN_TYPE}
                CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci {$null}
            ");
        }

        foreach ($foreignKeys as $fk) {
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
        // Intentionally no-op: reverting to mismatched collations would just reintroduce the bug.
    }

    /** Every column on a real (non-view) table that's utf8mb4 but not utf8mb4_unicode_ci, excluding binary collations. */
    private function targetColumns(): array
    {
        return DB::select("
            SELECT c.TABLE_NAME, c.COLUMN_NAME, c.COLUMN_TYPE, c.IS_NULLABLE
            FROM information_schema.COLUMNS c
            JOIN information_schema.TABLES t
                ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME
            WHERE c.TABLE_SCHEMA = DATABASE()
                AND t.TABLE_TYPE = 'BASE TABLE'
                AND c.TABLE_NAME != 'countries_old'
                AND c.CHARACTER_SET_NAME = 'utf8mb4'
                AND c.COLLATION_NAME != 'utf8mb4_unicode_ci'
                AND c.COLLATION_NAME NOT LIKE '%\\_bin'
        ");
    }

    /** Every FK (deduped) whose child or parent side is one of $targets, with its ON UPDATE/DELETE rules. */
    private function affectedForeignKeys(array $targets): array
    {
        $pairs = array_map(fn ($c) => "('{$c->TABLE_NAME}','{$c->COLUMN_NAME}')", $targets);
        $inList = implode(',', $pairs);

        $rows = DB::select("
            SELECT
                kcu.CONSTRAINT_NAME, kcu.TABLE_NAME, kcu.COLUMN_NAME,
                kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME,
                rc.UPDATE_RULE, rc.DELETE_RULE
            FROM information_schema.KEY_COLUMN_USAGE kcu
            JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                ON rc.CONSTRAINT_SCHEMA = kcu.TABLE_SCHEMA AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
            WHERE kcu.TABLE_SCHEMA = DATABASE()
                AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
                AND (
                    (kcu.TABLE_NAME, kcu.COLUMN_NAME) IN ({$inList})
                    OR (kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME) IN ({$inList})
                )
        ");

        $seen = [];
        $unique = [];
        foreach ($rows as $row) {
            $key = $row->TABLE_NAME.'.'.$row->CONSTRAINT_NAME;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $row;
        }

        return $unique;
    }
};
