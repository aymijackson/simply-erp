<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_journal_entry_lines')) {
            return;
        }

        Schema::create('finance_journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('finance_journal_entries')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('finance_accounts')->onDelete('cascade');
            $table->string('description')->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('memo')->nullable();
            $table->string('currency_code')->nullable();
            $table->decimal('fx_rate', 18, 6)->nullable();
            $table->string('party_type')->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->foreignId('bank_account_id')->nullable()->constrained('finance_bank_accounts')->nullOnDelete();
            $table->timestamps();

            $table->index(['party_type', 'party_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_journal_entry_lines');
    }
};
