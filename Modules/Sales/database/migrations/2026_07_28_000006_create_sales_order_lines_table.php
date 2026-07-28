<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('sales_order_lines')) {
            return;
        }

        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->onDelete('cascade');
            $table->foreignId('product_variant_id')->constrained('product_variants')->onDelete('cascade');
            $table->string('description')->nullable();
            $table->decimal('qty_ordered', 15, 4)->default(0);
            $table->decimal('qty_delivered', 15, 4)->default(0);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('line_total', 15, 4)->storedAs('qty_ordered * unit_price');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_lines');
    }
};
