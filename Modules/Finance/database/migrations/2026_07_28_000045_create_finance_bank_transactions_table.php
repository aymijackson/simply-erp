<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_bank_transactions')) {
            return;
        }

        Schema::create('finance_bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('txn_no')->nullable();
            $table->date('txn_date');
            $table->string('type'); // deposit, withdrawal, transfer
            $table->string('status')->default('draft'); // draft, posted, void
            $table->foreignId('bank_account_id')->constrained('finance_bank_accounts')->onDelete('cascade');
            $table->foreignId('to_bank_account_id')->nullable()->constrained('finance_bank_accounts')->nullOnDelete();
            $table->string('currency_code', 3)->nullable();
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->string('reference')->nullable();
            $table->string('description')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_bank_transactions');
    }
};
