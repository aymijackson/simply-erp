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
        if (Schema::hasTable('raw_material_instances')) {
            return;
        }

        Schema::create('raw_material_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->onDelete('cascade');
            $table->string('instance_name');
            $table->string('serial_number')->unique();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->onDelete('set null');
            // Reference to a stock record if needed
            $table->foreignId('stock_id')->nullable()->constrained('stock')->onDelete('set null');
            // Link to a lot if applicable
            $table->foreignId('raw_material_lot_id')->nullable()->constrained('raw_material_lots')->onDelete('set null');
            $table->string('warranty_terms')->nullable();
            // Optionally link an attribute value (for instance-specific attributes)
            $table->foreignId('raw_material_attribute_value_id')->nullable()->constrained('raw_material_attribute_values')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_material_instances');
    }
};
