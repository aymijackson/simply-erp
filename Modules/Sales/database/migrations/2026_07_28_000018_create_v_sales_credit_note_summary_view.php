<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<SQL
        CREATE OR REPLACE VIEW v_sales_credit_note_summary AS
        SELECT
            cn.id,
            cn.credit_note_no,
            cn.customer_id,
            cn.credit_note_date,
            cn.currency_code,
            cn.status,
            cn.grand_total AS total_amount
        FROM sales_credit_notes cn;
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_sales_credit_note_summary');
    }
};
