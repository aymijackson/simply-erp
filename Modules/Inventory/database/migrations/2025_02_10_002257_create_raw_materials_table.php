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
        Schema::create('raw_materials', function (Blueprint $table) {
            $table->id();
            $table->string('raw_material_code')->unique();
            $table->foreignId('category_id')->constrained('item_categories')->onDelete('cascade');
            $table->foreignId('group_id')->constrained('item_groups')->onDelete('cascade');
            $table->foreignId('brand_id')->nullable()->constrained('brands')->onDelete('set null');
            $table->foreignId('generic_id')->nullable()->constrained('generic_raw_materials')->onDelete('set null');
            // Model/Part ID (if needed)
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('raw_material_name');
            $table->text('raw_material_description')->nullable();
            $table->decimal('raw_material_price', 10, 2);
            $table->boolean('has_instances')->default(false);
            $table->boolean('has_lots')->default(false);
            $table->boolean('has_attributes')->default(false);
            $table->foreignId('default_uom')->nullable()->constrained('item_uoms')->onDelete('set null');
            $table->string('pack_size')->nullable();
            $table->decimal('average_cost', 10, 2)->nullable();
            $table->string('single_unit_raw_material_code')->nullable();
            $table->string('dimension_group')->nullable();
            $table->string('lot_information')->nullable();
            $table->string('warranty_terms')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('raw_material_stock_quantity')->default(0);
            $table->softDeletes(); // creates deleted_at column
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_materials');
    }
};
