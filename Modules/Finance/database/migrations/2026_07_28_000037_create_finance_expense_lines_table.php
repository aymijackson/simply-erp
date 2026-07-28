<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_expense_lines')) {
            return;
        }

        Schema::create('finance_expense_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained('finance_expenses')->onDelete('cascade');
            $table->string('description')->nullable();
            $table->foreignId('gl_account_id')->constrained('finance_accounts')->onDelete('cascade');
            $table->decimal('qty', 15, 4)->default(1);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->foreignId('tax_code_id')->nullable()->constrained('finance_tax_codes')->nullOnDelete();
            $table->foreignId('tax_rate_id')->nullable()->constrained('finance_tax_rates')->nullOnDelete();
            $table->decimal('tax_rate', 8, 4)->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->string('memo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_expense_lines');
    }
};
