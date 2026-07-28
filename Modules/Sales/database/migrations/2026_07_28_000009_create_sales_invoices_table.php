<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('sales_invoices')) {
            return;
        }

        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->nullable();
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->string('reference')->nullable();
            $table->text('remarks')->nullable();
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_total', 15, 4)->default(0);
            $table->decimal('grand_total', 15, 4)->default(0);
            $table->string('status')->default('draft'); // draft, posted, cancelled
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoices');
    }
};
