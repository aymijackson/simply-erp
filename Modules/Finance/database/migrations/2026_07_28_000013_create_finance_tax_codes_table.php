<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_tax_codes')) {
            return;
        }

        Schema::create('finance_tax_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('tax_type')->nullable();
            $table->foreignId('rate_id')->nullable()->constrained('finance_tax_rates')->nullOnDelete();
            $table->boolean('is_reverse_charge')->default(false);
            $table->boolean('is_exempt')->default(false);
            $table->boolean('is_out_of_scope')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_tax_codes');
    }
};
