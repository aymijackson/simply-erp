<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupplierAddressesTable extends Migration
{
    public function up()
    {
        Schema::create('supplier_addresses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('supplier_id');
            $table->enum('type', ['billing', 'shipping', 'headquarters', 'other'])->default('shipping');
            $table->string('line1');
            $table->string('line2')->nullable();

            // Replacing text-based city/state/country with foreign keys
            $table->unsignedMediumInteger('country_id');
            $table->unsignedMediumInteger('state_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();

            $table->string('postal_code')->nullable();

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('restrict');
            $table->foreign('state_id')->references('id')->on('states')->onDelete('restrict');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('supplier_addresses');
    }
}
