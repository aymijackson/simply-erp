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
        if (Schema::hasTable('product_lots')) {
            return;
        }

        Schema::create('product_lots', function (Blueprint $table) {
            $table->id();
            $table->string('lot_code')->unique();
            $table->date('date_manufactured')->nullable();
            $table->date('date_expiry')->nullable();
            // Optional reference to an attribute value (if needed)
            $table->foreignId('product_attribute_value_id')->nullable()->constrained('product_attribute_values')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_lots');
    }
};
