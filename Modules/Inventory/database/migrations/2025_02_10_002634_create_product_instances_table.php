<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('product_instances')) {
            return;
        }

        Schema::create('product_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('instance_name');
            $table->string('serial_number')->unique();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->onDelete('set null');
            // Reference to a stock record if needed
            $table->foreignId('stock_id')->nullable()->constrained('stock')->onDelete('set null');
            // Link to a lot if applicable
            $table->foreignId('product_lot_id')->nullable()->constrained('product_lots')->onDelete('set null');
            $table->string('warranty_terms')->nullable();
            // Optionally link an attribute value (for instance-specific attributes)
            $table->foreignId('product_attribute_value_id')->nullable()->constrained('product_attribute_values')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_instances');
    }
};
