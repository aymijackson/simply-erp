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
        Schema::create('location_block_floors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_block_id')->nullable();
            $table->string('name');
            $table->timestamps();
            $table->foreign('location_block_id')->references('id')->on('location_blocks')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_block_floors');
    }
};
