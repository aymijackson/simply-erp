<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_bank_accounts')) {
            return;
        }

        Schema::create('finance_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('name');
            $table->string('type')->nullable(); // bank, cash
            $table->string('currency_code')->nullable();
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('sort_code')->nullable();
            $table->string('iban')->nullable();
            $table->string('swift')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->date('opening_balance_date')->nullable();
            $table->foreignId('gl_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_bank_accounts');
    }
};
