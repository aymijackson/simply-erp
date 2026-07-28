<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_cashflow_mappings')) {
            return;
        }

        Schema::create('finance_cashflow_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('gl_account_id')->constrained('finance_accounts')->onDelete('cascade');
            $table->string('section'); // operating, investing, financing, non_cash
            $table->string('label')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'gl_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_cashflow_mappings');
    }
};
