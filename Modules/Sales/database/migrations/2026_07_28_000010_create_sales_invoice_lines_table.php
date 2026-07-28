<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('sales_invoice_lines')) {
            return;
        }

        Schema::create('sales_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->onDelete('cascade');
            $table->foreignId('sales_order_line_id')->nullable()->constrained('sales_order_lines')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('line_type')->default('item'); // item, charge
            $table->string('charge_code')->nullable();
            $table->string('description')->nullable();
            $table->decimal('qty_to_invoice', 15, 4)->default(0);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('line_total', 15, 4)->default(0);
            $table->boolean('is_taxable')->default(false);
            $table->decimal('tax_rate', 8, 4)->nullable();
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_lines');
    }
};
