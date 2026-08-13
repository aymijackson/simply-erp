<?php

// database/migrations/xxxx_xx_xx_000001_create_bom_deficits_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('bom_deficits')) {
            return;
        }

        Schema::create('bom_deficits', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('bom_id');                 // borrower BOM
            $t->unsignedBigInteger('product_variant_id');
            $t->decimal('qty_borrowed_total', 18, 6)->default(0);
            $t->decimal('qty_repaid_total',   18, 6)->default(0);
            $t->decimal('qty_outstanding',    18, 6)->default(0); // derived, cached
            $t->timestamp('last_txn_at')->nullable();
            $t->unsignedBigInteger('last_txn_id')->nullable();    // FK to txn if you like
            $t->timestamps();

            $t->unique(['bom_id','product_variant_id']);
            $t->index(['product_variant_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('bom_deficits'); }
};
