<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        /* 4 age buckets: 0‑30, 31‑60, 61‑90, 91+ (days) */
        DB::statement(<<<SQL
        CREATE OR REPLACE VIEW v_stock_age AS 
        SELECT   t.product_variant_id, t.location_store_id,
        DATEDIFF(CURDATE(), t.tx_date)          AS age_days,
        CASE
            WHEN DATEDIFF(CURDATE(), t.tx_date)  BETWEEN 0  AND 30  THEN '0‑30'
            WHEN DATEDIFF(CURDATE(), t.tx_date)  BETWEEN 31 AND 60  THEN '31‑60'
            WHEN DATEDIFF(CURDATE(), t.tx_date)  BETWEEN 61 AND 90  THEN '61‑90'
            ELSE '91+' 
        END                                        
        AS age_bucket,
        SUM(t.qty)                                 AS qty,
        SUM(t.qty * t.unit_cost)                   AS value
        FROM   stock_transactions t
        GROUP  BY t.product_variant_id, t.location_store_id, age_bucket, tx_date
        HAVING qty <> 0
        SQL);
    }

    public function down(): void
    { DB::statement('DROP VIEW IF EXISTS v_stock_age'); }
};
