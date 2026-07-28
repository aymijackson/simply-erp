<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_supplier_payment_allocations')) {
            return;
        }

        Schema::create('finance_supplier_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_payment_id')->constrained('finance_supplier_payments')->onDelete('cascade');
            $table->foreignId('supplier_bill_id')->constrained('finance_supplier_bills')->onDelete('cascade');
            $table->decimal('allocated_amount', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_supplier_payment_allocations');
    }
};
