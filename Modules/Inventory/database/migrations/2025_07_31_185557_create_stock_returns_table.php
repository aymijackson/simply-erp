<?php

/* 2025_08_01_0001_create_stock_returns_tables.php */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_returns', function (Blueprint $t) {
            $t->id();
            $t->enum('return_type',['customer','supplier']);
            $t->string('return_no')->unique();
            $t->foreignId('store_id')->constrained('location_stores');
            $t->foreignId('reference_id')->nullable();   // SO, DO, PO or Receipt
            $t->string('reference_type')->nullable();
            $t->text('reason')->nullable();
            $t->enum('status',['draft','approved','posted'])->default('draft');
            $t->timestamp('posted_at')->nullable();
            $t->foreignId('posted_by')->nullable()->constrained('users');
            $t->timestamps();
        });

        Schema::create('stock_return_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stock_return_id')->constrained();
            $t->foreignId('product_variant_id')->constrained();
            $t->decimal('qty',14,4);
            $t->decimal('unit_cost',14,4)->nullable();
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('stock_return_lines');
        Schema::dropIfExists('stock_returns');
    }
};
