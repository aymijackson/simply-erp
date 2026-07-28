<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('project_invoices')) {
            return;
        }

        Schema::create('project_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('invoice_no')->nullable();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('billing_method')->nullable(); // fixed, time_and_materials, milestone
            $table->string('currency_code')->nullable();
            $table->decimal('fx_rate', 18, 6)->nullable();
            $table->string('reference')->nullable();
            $table->text('memo')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2)->default(0);
            $table->string('status')->default('draft'); // draft, posted, partially_paid, paid, void
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_invoices');
    }
};
