<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductVariantsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('product_variants')) {
            return;
        }

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('sku')->unique();
            $table->decimal('price', 15, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->timestamps();
        });

        Schema::create('product_attribute_value_product_variant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->comment('FK to product_variants');
            $table->foreignId('product_attribute_value_id')->comment('FK to product_attribute_values');
            $table->timestamps();

            $table->foreign('product_variant_id', 'pavpv_variant_fk')
                ->references('id')->on('product_variants')->onDelete('cascade');
            $table->foreign('product_attribute_value_id', 'pavpv_attr_value_fk')
                ->references('id')->on('product_attribute_values')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_attribute_value_product_variant');
        Schema::dropIfExists('product_variants');
    }
}
