<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('proc_purchase_requisition_lines')) {
            return;
        }

        Schema::create('proc_purchase_requisition_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_requisition_id')->constrained('proc_purchase_requisitions')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->string('description')->nullable();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('qty', 15, 4);
            $table->decimal('estimated_unit_cost', 15, 4)->default(0);
            $table->unsignedBigInteger('tax_code_id')->nullable();
            $table->unsignedBigInteger('tax_rate_id')->nullable();
            $table->decimal('tax_rate', 8, 4)->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('location_stores')->nullOnDelete();
            $table->string('memo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proc_purchase_requisition_lines');
    }
};
