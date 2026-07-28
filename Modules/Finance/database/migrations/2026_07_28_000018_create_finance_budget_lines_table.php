<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_budget_lines')) {
            return;
        }

        Schema::create('finance_budget_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('finance_budgets')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('finance_accounts')->onDelete('cascade');
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_budget_lines');
    }
};
