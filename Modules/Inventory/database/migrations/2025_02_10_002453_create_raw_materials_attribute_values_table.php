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
        if (Schema::hasTable('raw_material_attribute_values')) {
            return;
        }

        Schema::create('raw_material_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_material_attribute_id')->constrained('raw_material_attributes')->onDelete('cascade');
            $table->string('value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_material_attribute_values');
    }
};
