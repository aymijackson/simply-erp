<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<SQL
        CREATE OR REPLACE VIEW v_ar_open_invoices AS
        SELECT
            i.id AS invoice_id,
            i.invoice_no,
            i.customer_id,
            c.name AS customer_name,
            i.invoice_date,
            i.due_date,
            i.currency_code,
            i.grand_total,
            COALESCE(alloc.paid_total, 0)                    AS total_paid,
            (i.grand_total - COALESCE(alloc.paid_total, 0))  AS balance_due
        FROM sales_invoices i
        JOIN customers c ON c.id = i.customer_id
        LEFT JOIN (
            SELECT sales_invoice_id, SUM(amount_applied) AS paid_total
            FROM sales_payment_allocations
            GROUP BY sales_invoice_id
        ) alloc ON alloc.sales_invoice_id = i.id
        WHERE i.status IN ('posted', 'paid');
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_ar_open_invoices');
    }
};
