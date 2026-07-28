<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('petty_cash_audits')) {
            return;
        }

        Schema::create('petty_cash_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('petty_cash_account_id')->nullable()->constrained('petty_cash_accounts')->nullOnDelete();
            $table->foreignId('petty_cash_transaction_id')->nullable()->constrained('petty_cash_transactions')->nullOnDelete();
            $table->foreignId('reconciliation_id')->nullable()->constrained('petty_cash_reconciliations')->nullOnDelete();
            $table->string('action');
            $table->string('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_cash_audits');
    }
};
