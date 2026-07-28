<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_accounts')) {
            return;
        }

        Schema::create('finance_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('account_type_id')->constrained('finance_account_types')->onDelete('cascade');
            $table->string('code');
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->boolean('is_control')->default(false);
            $table->boolean('allow_manual_posting')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_accounts');
    }
};
