<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('proc_goods_receipt_lines')) {
            return;
        }

        Schema::create('proc_goods_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained('proc_goods_receipts')->onDelete('cascade');
            $table->foreignId('purchase_order_line_id')->constrained('proc_purchase_order_lines')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->string('description')->nullable();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('ordered_qty', 15, 4)->default(0);
            $table->decimal('previously_received_qty', 15, 4)->default(0);
            $table->decimal('received_qty', 15, 4)->default(0);
            $table->decimal('remaining_qty', 15, 4)->default(0);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->decimal('accepted_qty', 15, 4)->default(0);
            $table->decimal('rejected_qty', 15, 4)->default(0);
            $table->decimal('damage_qty', 15, 4)->default(0);
            $table->string('batch_no')->nullable();
            $table->string('serial_no')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proc_goods_receipt_lines');
    }
};
