<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_bank_transaction_lines')) {
            return;
        }

        Schema::create('finance_bank_transaction_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_transaction_id')->constrained('finance_bank_transactions')->onDelete('cascade');
            $table->unsignedInteger('line_no')->default(1);
            $table->foreignId('account_id')->constrained('finance_accounts')->onDelete('cascade');
            $table->string('memo')->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_bank_transaction_lines');
    }
};
