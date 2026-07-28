<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('petty_cash_reconciliations')) {
            return;
        }

        Schema::create('petty_cash_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('petty_cash_account_id')->constrained('petty_cash_accounts')->onDelete('cascade');
            $table->string('reconciliation_no')->nullable();
            $table->date('reconciliation_date');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('funds_added', 15, 2)->default(0);
            $table->decimal('expenses_total', 15, 2)->default(0);
            $table->decimal('refunds_total', 15, 2)->default(0);
            $table->decimal('closing_balance_system', 15, 2)->default(0);
            $table->decimal('closing_balance_counted', 15, 2)->default(0);
            $table->decimal('variance_amount', 15, 2)->default(0);
            $table->string('status')->default('draft'); // draft, posted, void
            $table->text('notes')->nullable();
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
        Schema::dropIfExists('petty_cash_reconciliations');
    }
};
