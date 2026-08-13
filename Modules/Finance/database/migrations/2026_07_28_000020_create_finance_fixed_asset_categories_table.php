<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_fixed_asset_categories')) {
            return;
        }

        Schema::create('finance_fixed_asset_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('name');
            $table->string('code')->nullable();
            $table->foreignId('default_asset_account_id')->nullable();
            $table->foreignId('default_accum_depr_account_id')->nullable();
            $table->foreignId('default_depr_expense_account_id')->nullable();
            $table->foreignId('default_disposal_gain_account_id')->nullable();
            $table->foreignId('default_disposal_loss_account_id')->nullable();
            $table->string('default_depr_method')->nullable(); // straight_line, reducing_balance
            $table->unsignedInteger('default_useful_life_months')->nullable();
            $table->decimal('default_salvage_value', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('default_asset_account_id', 'fac_default_asset_acct_fk')
                ->references('id')->on('finance_accounts')->nullOnDelete();
            $table->foreign('default_accum_depr_account_id', 'fac_default_accum_depr_acct_fk')
                ->references('id')->on('finance_accounts')->nullOnDelete();
            $table->foreign('default_depr_expense_account_id', 'fac_default_depr_exp_acct_fk')
                ->references('id')->on('finance_accounts')->nullOnDelete();
            $table->foreign('default_disposal_gain_account_id', 'fac_default_disposal_gain_acct_fk')
                ->references('id')->on('finance_accounts')->nullOnDelete();
            $table->foreign('default_disposal_loss_account_id', 'fac_default_disposal_loss_acct_fk')
                ->references('id')->on('finance_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_fixed_asset_categories');
    }
};
