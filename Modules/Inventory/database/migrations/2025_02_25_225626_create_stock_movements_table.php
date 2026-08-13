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
        if (Schema::hasTable('stock_movements')) {
            return;
        }

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('product_instance_id')->nullable()->constrained('product_instances')->onDelete('set null');
            $table->foreignId('product_lot_id')->nullable()->constrained('product_lots')->onDelete('set null');
            $table->foreignId('supplier_id')->nullable();
            $table->enum('movement_type', ['inbound', 'outbound', 'manufacturing', 'adjustment']);
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2)->nullable(); // Price per unit (if applicable)
            $table->decimal('total_value', 10, 2)->nullable();
            $table->string('reference_number')->nullable(); // Reference to invoice, order, etc.
            $table->string('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null'); // The user processing the transaction
            $table->timestamp('movement_date')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
