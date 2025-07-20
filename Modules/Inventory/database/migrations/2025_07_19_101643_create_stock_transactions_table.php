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
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained();
            $table->foreignId('location_store_id')->constrained();
            $table->enum('tx_type',['ENTRY','ISSUE','ADJUST','TRANSFER_IN','TRANSFER_OUT']);
            $table->unsignedInteger('qty');        // positive numbers only
            $table->decimal('unit_cost',14,4)->nullable();
            $table->morphs('txable');              // txable_id + txable_type (polymorphic back‑link)
            $table->timestamp('tx_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transactions');
    }
};
