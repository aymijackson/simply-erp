<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('sales_delivery_lines')) {
            return;
        }

        Schema::create('sales_delivery_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_delivery_id')->constrained('sales_deliveries')->onDelete('cascade');
            $table->foreignId('sales_order_line_id')->constrained('sales_order_lines')->onDelete('cascade');
            $table->foreignId('location_store_id')->nullable()->constrained('location_stores')->nullOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->onDelete('cascade');
            $table->decimal('qty_to_deliver', 15, 4)->default(0);
            $table->decimal('qty_delivered_actual', 15, 4)->default(0);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_delivery_lines');
    }
};
