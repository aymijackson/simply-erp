<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('sales_credit_notes')) {
            return;
        }

        Schema::create('sales_credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('sales_invoice_id')->nullable()->constrained('sales_invoices')->nullOnDelete();
            $table->foreignId('stock_return_id')->nullable()->constrained('stock_returns')->nullOnDelete();
            $table->string('credit_note_no')->nullable();
            $table->date('credit_note_date');
            $table->string('currency_code', 3)->nullable();
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_total', 15, 4)->default(0);
            $table->decimal('grand_total', 15, 4)->default(0);
            $table->string('reason')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status')->default('draft'); // draft, posted, void
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_credit_notes');
    }
};
