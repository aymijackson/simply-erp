<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('petty_cash_accounts')) {
            return;
        }

        Schema::create('petty_cash_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('account_code')->nullable();
            $table->string('name');
            $table->foreignId('custodian_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('gl_cash_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('gl_expense_clearing_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->decimal('minimum_balance', 15, 2)->nullable();
            $table->boolean('auto_replenish_suggestion')->default(false);
            $table->decimal('float_amount', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->string('status')->default('active'); // active, inactive, closed
            $table->text('notes')->nullable();
            $table->timestamp('last_replenished_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_cash_accounts');
    }
};
