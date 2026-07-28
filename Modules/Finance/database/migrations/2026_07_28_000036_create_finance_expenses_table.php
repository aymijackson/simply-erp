<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_expenses')) {
            return;
        }

        Schema::create('finance_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('expense_no')->nullable();
            $table->date('expense_date');
            $table->foreignId('category_id')->nullable()->constrained('finance_expense_categories')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('vendor_name')->nullable();
            $table->string('reference')->nullable();
            $table->text('memo')->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->decimal('fx_rate', 18, 6)->nullable();
            $table->string('payment_mode')->default('cash'); // cash, bank, credit
            $table->foreignId('bank_account_id')->nullable()->constrained('finance_bank_accounts')->nullOnDelete();
            $table->foreignId('payable_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
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
        Schema::dropIfExists('finance_expenses');
    }
};
