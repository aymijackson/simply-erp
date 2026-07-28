<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_asset_capitalisations')) {
            return;
        }

        Schema::create('finance_asset_capitalisations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('source_module')->nullable();
            $table->string('source_table')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('reference_no')->nullable();
            $table->foreignId('asset_category_id')->nullable()->constrained('finance_fixed_asset_categories')->nullOnDelete();
            $table->string('asset_name');
            $table->text('asset_description')->nullable();
            $table->decimal('quantity', 15, 2)->default(1);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->date('purchase_date')->nullable();
            $table->date('in_service_date')->nullable();
            $table->string('status')->default('pending'); // pending, converted, void
            $table->foreignId('converted_asset_id')->nullable()->constrained('finance_fixed_assets')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('converted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_asset_capitalisations');
    }
};
