<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_order_headers') && Schema::hasColumn('purchase_order_headers', 'supplier_id') && Schema::hasTable('suppliers') && !$this->hasForeign('poh_supplier_fk')) {
            Schema::table('purchase_order_headers', function (Blueprint $table) {
                $table->foreign('supplier_id', 'poh_supplier_fk')->references('id')->on('suppliers')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('stock_movements') && Schema::hasColumn('stock_movements', 'supplier_id') && Schema::hasTable('suppliers') && !$this->hasForeign('stkmv_supplier_fk')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->foreign('supplier_id', 'stkmv_supplier_fk')->references('id')->on('suppliers')->nullOnDelete();
            });
        }

        if (Schema::hasTable('stock_entries') && Schema::hasColumn('stock_entries', 'product_variant_id') && Schema::hasTable('product_variants') && !$this->hasForeign('stkent_variant_fk')) {
            Schema::table('stock_entries', function (Blueprint $table) {
                $table->foreign('product_variant_id', 'stkent_variant_fk')->references('id')->on('product_variants')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchase_order_headers') && $this->hasForeign('poh_supplier_fk')) {
            Schema::table('purchase_order_headers', fn (Blueprint $table) => $table->dropForeign('poh_supplier_fk'));
        }
        if (Schema::hasTable('stock_movements') && $this->hasForeign('stkmv_supplier_fk')) {
            Schema::table('stock_movements', fn (Blueprint $table) => $table->dropForeign('stkmv_supplier_fk'));
        }
        if (Schema::hasTable('stock_entries') && $this->hasForeign('stkent_variant_fk')) {
            Schema::table('stock_entries', fn (Blueprint $table) => $table->dropForeign('stkent_variant_fk'));
        }
    }

    private function hasForeign(string $constraint): bool
    {
        $conn = Schema::getConnection();

        return $conn->table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $conn->getDatabaseName())
            ->where('CONSTRAINT_NAME', $constraint)
            ->exists();
    }
};
