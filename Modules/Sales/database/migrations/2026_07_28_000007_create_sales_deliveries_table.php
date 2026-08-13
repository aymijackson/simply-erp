<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('sales_deliveries')) {
            return;
        }

        Schema::create('sales_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_no')->nullable();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->onDelete('cascade');
            $table->foreignId('driver_id')->nullable();
            $table->foreignId('vehicle_id')->nullable();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('location_store_id')->nullable()->constrained('location_stores')->nullOnDelete();
            $table->date('ship_date')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('status')->default('draft'); // draft, posted, cancelled
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_deliveries');
    }
};
