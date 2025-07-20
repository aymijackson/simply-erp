<?php

/* database/migrations/2025_07_22_0001_create_v_stock_levels_view.php */
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<SQL
CREATE OR REPLACE VIEW v_stock_levels AS
SELECT
    product_variant_id,
    location_store_id,
    SUM(
        CASE
            WHEN tx_type IN ('ENTRY','TRANSFER_IN','ADJUST_POS')  THEN qty
            WHEN tx_type IN ('ISSUE','TRANSFER_OUT','ADJUST_NEG') THEN -qty
            ELSE 0
        END
    )                       AS qty_on_hand,
    SUM(
        CASE
            WHEN unit_cost IS NULL THEN 0
            WHEN tx_type IN ('ENTRY','TRANSFER_IN','ADJUST_POS')  THEN qty * unit_cost
            WHEN tx_type IN ('ISSUE','TRANSFER_OUT','ADJUST_NEG') THEN -qty * unit_cost
            ELSE 0
        END
    )                       AS value_on_hand
FROM stock_transactions
GROUP BY product_variant_id, location_store_id;
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_stock_levels');
    }
};
