<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('sales_orders')) {
            return;
        }

        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->nullable();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->date('order_date');
            $table->string('currency_code', 3)->nullable();
            $table->string('status')->default('draft'); // draft, confirmed, cancelled, closed
            $table->string('reference')->nullable();
            $table->text('remarks')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
