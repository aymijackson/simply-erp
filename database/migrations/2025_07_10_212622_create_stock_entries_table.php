<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockEntriesTable extends Migration
{
    public function up()
    {
        Schema::create('stock_entries', function (Blueprint $table) {
            $table->id();
            
            // Reference to product variant
            $table->foreignId('product_variant_id')->constrained('product_variants')->onDelete('cascade');

            // Optional store and shelf
            $table->foreignId('store_id')->constrained('location_stores')->onDelete('set null')->nullable();
            $table->foreignId('shelf_id')->nullable()->constrained('store_shelves')->onDelete('set null');

            $table->integer('quantity')->unsigned();
            $table->date('entry_date')->default(now());

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_entries');
    }
}
