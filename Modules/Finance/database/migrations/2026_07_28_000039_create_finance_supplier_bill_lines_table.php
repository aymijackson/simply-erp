<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_supplier_bill_lines')) {
            return;
        }

        Schema::create('finance_supplier_bill_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained('finance_supplier_bills')->onDelete('cascade');
            $table->foreignId('purchase_requisition_line_id')->nullable()->constrained('proc_purchase_requisition_lines')->nullOnDelete();
            $table->foreignId('rfq_line_id')->nullable()->constrained('proc_request_for_quotation_lines')->nullOnDelete();
            $table->foreignId('supplier_quotation_line_id')->nullable()->constrained('proc_supplier_quotation_lines')->nullOnDelete();
            $table->foreignId('purchase_order_line_id')->nullable()->constrained('proc_purchase_order_lines')->nullOnDelete();
            $table->foreignId('goods_receipt_line_id')->nullable()->constrained('proc_goods_receipt_lines')->nullOnDelete();
            $table->string('description')->nullable();
            $table->foreignId('gl_account_id')->constrained('finance_accounts')->onDelete('cascade');
            $table->decimal('qty', 15, 4)->default(1);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->decimal('tax_rate', 8, 4)->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->string('memo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_supplier_bill_lines');
    }
};
