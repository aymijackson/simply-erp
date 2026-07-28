<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('proc_purchase_requisitions')) {
            return;
        }

        Schema::create('proc_purchase_requisitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('requisition_no')->nullable();
            $table->date('requisition_date');
            $table->date('needed_by_date')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->string('status')->default('draft'); // draft, submitted, approved, rejected, cancelled, converted
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'requisition_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proc_purchase_requisitions');
    }
};
