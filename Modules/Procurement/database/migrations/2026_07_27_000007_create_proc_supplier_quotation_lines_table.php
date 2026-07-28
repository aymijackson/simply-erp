<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('proc_supplier_quotation_lines')) {
            return;
        }

        Schema::create('proc_supplier_quotation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_quotation_id')->constrained('proc_supplier_quotations')->onDelete('cascade');
            $table->foreignId('rfq_line_id')->nullable()->constrained('proc_request_for_quotation_lines')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->string('description')->nullable();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('qty', 15, 4);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('discount_percent', 8, 4)->nullable();
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->unsignedBigInteger('tax_code_id')->nullable();
            $table->unsignedBigInteger('tax_rate_id')->nullable();
            $table->decimal('tax_rate', 8, 4)->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proc_supplier_quotation_lines');
    }
};
