<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_bank_reconciliations')) {
            return;
        }

        Schema::create('finance_bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('bank_account_id')->constrained('finance_bank_accounts')->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('statement_opening_balance', 15, 2)->default(0);
            $table->decimal('statement_closing_balance', 15, 2)->default(0);
            $table->decimal('system_opening_balance', 15, 2)->default(0);
            $table->decimal('system_closing_balance', 15, 2)->default(0);
            $table->string('status')->default('in_progress'); // in_progress, closed
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_bank_reconciliations');
    }
};
