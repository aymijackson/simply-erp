<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('proc_supplier_quotations')) {
            return;
        }

        Schema::create('proc_supplier_quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('rfq_id')->nullable()->constrained('proc_request_for_quotations')->nullOnDelete();
            $table->foreignId('rfq_supplier_id')->nullable()->constrained('proc_request_for_quotation_suppliers')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->string('quotation_no')->nullable();
            $table->string('supplier_quote_no')->nullable();
            $table->date('quotation_date');
            $table->date('valid_until')->nullable();
            $table->string('currency_code')->nullable();
            $table->decimal('fx_rate', 18, 6)->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status')->default('draft'); // draft, submitted, reviewed, accepted, rejected
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proc_supplier_quotations');
    }
};
