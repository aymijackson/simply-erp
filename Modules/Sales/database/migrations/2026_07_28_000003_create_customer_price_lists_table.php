<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('customer_price_lists')) {
            return;
        }

        Schema::create('customer_price_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('price_list_id')->constrained('price_lists')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['customer_id', 'price_list_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_price_lists');
    }
};
