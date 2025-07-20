<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductVariantsTable extends Migration
{
    public function up()
    {
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
            $table->foreignId('product_variant_id')
                ->constrained()
                ->onDelete('cascade')
                ->comment('FK to product_variants')
                ->name('fk_variant');

            $table->foreignId('product_attribute_value_id')
                ->constrained()
                ->onDelete('cascade')
                ->comment('FK to product_attribute_values')
                ->name('fk_attr_value');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_attribute_value_product_variant');
        Schema::dropIfExists('product_variants');
    }
}
