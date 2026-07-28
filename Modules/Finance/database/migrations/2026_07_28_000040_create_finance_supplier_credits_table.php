<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_supplier_credits')) {
            return;
        }

        Schema::create('finance_supplier_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('credit_no')->nullable();
            $table->date('credit_date');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');

            $table->foreignId('purchase_order_id')->nullable()->constrained('proc_purchase_orders')->nullOnDelete();
            $table->foreignId('goods_receipt_id')->nullable()->constrained('proc_goods_receipts')->nullOnDelete();
            $table->foreignId('supplier_bill_id')->nullable()->constrained('finance_supplier_bills')->nullOnDelete();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->foreignId('ap_control_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->string('currency_code', 3)->nullable();
            $table->decimal('fx_rate', 18, 6)->nullable();

            $table->string('reference')->nullable();
            $table->text('memo')->nullable();

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('unapplied_amount', 15, 2)->default(0);

            $table->string('status')->default('draft'); // draft, posted, voided
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
        Schema::dropIfExists('finance_supplier_credits');
    }
};
