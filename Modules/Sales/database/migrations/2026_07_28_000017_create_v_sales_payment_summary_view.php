<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<SQL
        CREATE OR REPLACE VIEW v_sales_payment_summary AS
        SELECT
            p.id,
            p.payment_no,
            p.customer_id,
            p.payment_date,
            p.currency_code,
            p.status,
            p.amount_received,
            COALESCE(alloc.allocated_total, 0)                        AS allocated_total,
            (p.amount_received - COALESCE(alloc.allocated_total, 0))  AS unallocated_total
        FROM sales_payments p
        LEFT JOIN (
            SELECT sales_payment_id, SUM(amount_applied) AS allocated_total
            FROM sales_payment_allocations
            GROUP BY sales_payment_id
        ) alloc ON alloc.sales_payment_id = p.id;
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_sales_payment_summary');
    }
};
