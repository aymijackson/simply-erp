<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_fixed_asset_components')) {
            return;
        }

        Schema::create('finance_fixed_asset_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('parent_asset_id')->constrained('finance_fixed_assets')->onDelete('cascade');
            $table->string('component_code')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('cost', 15, 2)->default(0);
            $table->decimal('salvage_value', 15, 2)->nullable();
            $table->string('depr_method')->nullable();
            $table->unsignedInteger('useful_life_months')->nullable();
            $table->decimal('depr_rate', 8, 4)->nullable();
            $table->foreignId('asset_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('accum_depr_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('depr_expense_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_fixed_asset_components');
    }
};
