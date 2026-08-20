<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The original 2025_07_15_062329_create_raw_materials_table migration is recorded as run,
// but production_raw_materials doesn't exist on the real DB (its Schema::hasTable() guard
// must have short-circuited it at some point). RawMaterialController/RawMaterial model
// depend on this table for every request, so recreate it if missing.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('production_raw_materials')) {
            return;
        }

        Schema::create('production_raw_materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->foreignId('unit_id')->constrained()->onDelete('cascade');
            $table->decimal('cost', 15, 2)->nullable();
            $table->decimal('cost_per_unit', 15, 2)->nullable();
            $table->decimal('restock_level', 15, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_raw_materials');
    }
};
