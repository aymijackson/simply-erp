<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('proc_request_for_quotations')) {
            return;
        }

        Schema::create('proc_request_for_quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('requisition_id')->nullable()->constrained('proc_purchase_requisitions')->nullOnDelete();
            $table->string('rfq_no')->nullable();
            $table->date('rfq_date');
            $table->date('closing_date')->nullable();
            $table->string('currency_code')->nullable();
            $table->decimal('fx_rate', 18, 6)->nullable();
            $table->string('status')->default('draft');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'rfq_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proc_request_for_quotations');
    }
};
