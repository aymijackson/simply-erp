<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_supplier_payments')) {
            return;
        }

        Schema::create('finance_supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('payment_no')->nullable();
            $table->date('payment_date');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->string('currency_code', 3)->nullable();
            $table->decimal('fx_rate', 18, 6)->nullable();
            $table->foreignId('bank_account_id')->constrained('finance_bank_accounts')->onDelete('cascade');
            $table->foreignId('ap_control_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->string('reference')->nullable();
            $table->text('memo')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
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
        Schema::dropIfExists('finance_supplier_payments');
    }
};
