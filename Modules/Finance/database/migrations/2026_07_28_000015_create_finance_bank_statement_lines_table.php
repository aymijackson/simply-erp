<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_bank_statement_lines')) {
            return;
        }

        Schema::create('finance_bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('reconciliation_id')->constrained('finance_bank_reconciliations')->onDelete('cascade');
            $table->date('txn_date');
            $table->string('description')->nullable();
            $table->string('reference')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('fit_id')->nullable();
            $table->json('raw_payload')->nullable();
            $table->string('status')->default('unmatched'); // unmatched, matched, excluded
            $table->string('exclude_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_bank_statement_lines');
    }
};
