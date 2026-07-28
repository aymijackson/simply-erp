<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('price_list_items')) {
            return;
        }

        Schema::create('price_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_list_id')->constrained('price_lists')->onDelete('cascade');
            $table->foreignId('product_variant_id')->constrained('product_variants')->onDelete('cascade');
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('min_qty', 15, 4)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_items');
    }
};
