<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('sales_payments')) {
            return;
        }

        Schema::create('sales_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('payment_no')->nullable();
            $table->date('payment_date');
            $table->string('currency_code', 3)->nullable();
            $table->decimal('amount_received', 15, 2)->default(0);
            $table->string('method')->nullable(); // cash, bank_transfer, cheque, card, other
            $table->string('reference')->nullable();
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
        Schema::dropIfExists('sales_payments');
    }
};
