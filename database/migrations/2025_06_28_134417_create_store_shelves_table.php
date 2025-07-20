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
        Schema::create('store_shelves', function (Blueprint $table) {
            $table->id();

            $table->foreignId('location_store_id')
                  ->constrained('location_stores')
                  ->onDelete('cascade');

            $table->string('name'); // e.g., SH-A1, SH-B2
            $table->string('description')->nullable(); // Optional label or purpose
            $table->integer('capacity')->nullable(); // Optional maximum capacity

            $table->timestamps();

            // Enforce unique shelf codes per store
            $table->unique(['location_store_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_shelves');
    }
};
