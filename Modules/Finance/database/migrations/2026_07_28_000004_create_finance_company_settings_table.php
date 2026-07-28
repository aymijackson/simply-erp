<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_company_settings')) {
            return;
        }

        Schema::create('finance_company_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('retained_earnings_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('income_summary_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->boolean('allow_post_to_closed_period')->default(false);
            $table->boolean('restrict_future_posting')->default(false);
            $table->timestamps();

            $table->unique('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_company_settings');
    }
};
