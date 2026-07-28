<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_fixed_assets')) {
            return;
        }

        Schema::create('finance_fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('finance_fixed_asset_categories')->nullOnDelete();
            $table->string('asset_code')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('in_service_date')->nullable();
            $table->decimal('purchase_cost', 15, 2)->default(0);
            $table->decimal('salvage_value', 15, 2)->nullable();
            $table->string('depr_method')->nullable(); // straight_line, reducing_balance
            $table->unsignedInteger('useful_life_months')->nullable();
            $table->decimal('depr_rate', 8, 4)->nullable();
            $table->foreignId('asset_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('accum_depr_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('depr_expense_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('disposal_gain_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('disposal_loss_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->string('location')->nullable();
            $table->string('serial_no')->nullable();
            $table->string('supplier_name')->nullable();
            $table->string('invoice_no')->nullable();
            $table->string('status')->default('active'); // active, disposed, written_off
            $table->date('disposal_date')->nullable();
            $table->decimal('disposal_proceeds', 15, 2)->nullable();
            $table->text('disposal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_fixed_assets');
    }
};
