<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupplierProductsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('supplier_products')) {
            return;
        }

        Schema::create('supplier_products', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('unit_cost', 10, 2);
            $table->integer('min_order_qty')->default(1);
            $table->integer('lead_time_days')->nullable();
            $table->timestamp('last_cost_change')->useCurrent();

            $table->primary(['supplier_id', 'product_id']);
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('supplier_products');
    }
}
