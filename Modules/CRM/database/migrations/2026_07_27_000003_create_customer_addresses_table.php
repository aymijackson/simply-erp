<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('customer_addresses')) {
            return;
        }

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('type')->nullable(); // billing, shipping, other
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->unsignedMediumInteger('country_id')->nullable();
            $table->unsignedMediumInteger('state_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('postal_code')->nullable();
            $table->boolean('is_default')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('country_id')->references('id')->on('countries')->onDelete('restrict');
            $table->foreign('state_id')->references('id')->on('states')->onDelete('restrict');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
