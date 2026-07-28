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
        if (Schema::hasTable('work_order_materials')) {
            return;
        }

        Schema::create('work_order_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('bom_item_id')->nullable()->constrained('bom_items')->onDelete('set null');
            $table->foreignId('product_variant_id')->constrained('product_variants')->onDelete('restrict');
            $table->decimal('planned_qty', 15, 4)->default(0);
            $table->decimal('issued_qty', 15, 4)->default(0);
            $table->decimal('returned_qty', 15, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['work_order_id', 'product_variant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_materials');
    }
};
