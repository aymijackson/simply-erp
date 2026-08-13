<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('stock_transfers')) {
            return;
        }

        Schema::create('stock_transfers', function (Blueprint $t) {
            $t->id();
            $t->string('transfer_no')->unique();
            $t->foreignId('from_store_id')->constrained('location_stores');
            $t->foreignId('to_store_id')->constrained('location_stores');
            $t->string('reason')->nullable();
            $t->foreignId('requested_by')->constrained('users');
            $t->foreignId('approved_by')->nullable()->constrained('users');
            $t->timestamp('posted_at')->nullable();
            $t->enum('status', ['draft','posted','void'])->default('draft');
            $t->timestamps();
        });

        Schema::create('stock_transfer_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stock_transfer_id')
              ->constrained()->cascadeOnDelete();
            $t->foreignId('product_variant_id')->constrained('product_variants');
            $t->integer('qty');
            $t->decimal('unit_cost', 12, 4)->nullable();   // will copy from last cost
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_lines');
        Schema::dropIfExists('stock_transfers');
    }
};
