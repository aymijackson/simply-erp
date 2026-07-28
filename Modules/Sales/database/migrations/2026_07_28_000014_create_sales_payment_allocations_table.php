<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('sales_payment_allocations')) {
            return;
        }

        Schema::create('sales_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_payment_id')->constrained('sales_payments')->onDelete('cascade');
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->onDelete('cascade');
            $table->decimal('amount_applied', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_payment_allocations');
    }
};
