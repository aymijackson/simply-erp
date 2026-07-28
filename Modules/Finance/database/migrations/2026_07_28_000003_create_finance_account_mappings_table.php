<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_account_mappings')) {
            return;
        }

        Schema::create('finance_account_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('ar_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('ap_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('sales_revenue_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('cogs_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('inventory_asset_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('retained_earnings_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('sales_discount_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('purchase_discount_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('rounding_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('default_bank_gl_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('vat_output_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('vat_input_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->timestamps();

            $table->unique('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_account_mappings');
    }
};
