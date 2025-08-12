<?php
/* database/migrations/2025_08_01_0000_create_v_stock_layers_view.php */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<SQL
CREATE OR REPLACE VIEW v_stock_layers AS
SELECT  id,
        product_variant_id,
        location_store_id,
        qty                 AS layer_qty,
        unit_cost,
        created_at
FROM    stock_transactions
WHERE   tx_type IN ('ENTRY','TRANSFER_IN','RETURN_IN','ADJUST_POS');
SQL);
    }
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_stock_layers');
    }
};
