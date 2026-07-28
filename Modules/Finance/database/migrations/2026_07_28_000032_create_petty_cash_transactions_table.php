<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('petty_cash_transactions')) {
            return;
        }

        Schema::create('petty_cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('petty_cash_account_id')->constrained('petty_cash_accounts')->onDelete('cascade');
            $table->string('transaction_no')->nullable();
            $table->string('voucher_no')->nullable();
            $table->date('transaction_date');
            $table->string('type'); // replenishment, expense, refund, adjustment
            $table->string('reference_no')->nullable();
            $table->string('payee_type')->nullable(); // employee, supplier, customer, other
            $table->unsignedBigInteger('payee_id')->nullable();
            $table->string('payee')->nullable();
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('status')->default('draft'); // draft, posted, void
            $table->string('workflow_status')->default('pending'); // pending, submitted, approved, rejected
            $table->foreignId('expense_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('finance_journal_entry_id')->nullable()->constrained('finance_journal_entries')->nullOnDelete();
            $table->string('attachment')->nullable();
            $table->string('attachment_original_name')->nullable();
            $table->string('attachment_mime_type')->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();
            $table->text('approval_notes')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_cash_transactions');
    }
};
