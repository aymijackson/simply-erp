<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_supplier_bills')) {
            return;
        }

        Schema::create('finance_supplier_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('bill_no')->nullable();
            $table->date('bill_date');
            $table->date('due_date')->nullable();

            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('vendor_name')->nullable();

            $table->foreignId('purchase_requisition_id')->nullable()->constrained('proc_purchase_requisitions')->nullOnDelete();
            $table->foreignId('rfq_id')->nullable()->constrained('proc_request_for_quotations')->nullOnDelete();
            $table->foreignId('supplier_quotation_id')->nullable()->constrained('proc_supplier_quotations')->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('proc_purchase_orders')->nullOnDelete();
            $table->foreignId('goods_receipt_id')->nullable()->constrained('proc_goods_receipts')->nullOnDelete();

            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->string('currency_code', 3)->nullable();
            $table->decimal('fx_rate', 18, 6)->nullable();

            $table->string('reference')->nullable();
            $table->text('memo')->nullable();

            $table->foreignId('payable_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2)->default(0);

            $table->string('status')->default('draft'); // draft, posted, part_paid, paid, voided
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_supplier_bills');
    }
};
