<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<SQL
        CREATE OR REPLACE VIEW v_sales_invoice_balances AS
        SELECT
            i.id AS sales_invoice_id,
            i.invoice_no,
            i.customer_id,
            i.invoice_date,
            i.due_date,
            i.currency_code,
            i.status,
            i.grand_total,
            COALESCE(alloc.paid_total, 0)                    AS amount_paid,
            (i.grand_total - COALESCE(alloc.paid_total, 0))  AS balance_due
        FROM sales_invoices i
        LEFT JOIN (
            SELECT sales_invoice_id, SUM(amount_applied) AS paid_total
            FROM sales_payment_allocations
            GROUP BY sales_invoice_id
        ) alloc ON alloc.sales_invoice_id = i.id
        WHERE i.status = 'posted';
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_sales_invoice_balances');
    }
};
