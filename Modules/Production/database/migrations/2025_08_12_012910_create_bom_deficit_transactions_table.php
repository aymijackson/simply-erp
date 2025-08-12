<?php

// database/migrations/xxxx_xx_xx_000002_create_bom_deficit_transactions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bom_deficit_transactions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('bom_id');                  // borrower BOM
            $t->unsignedBigInteger('product_variant_id');
            $t->enum('direction', ['borrow','repay','writeoff','adjust']); 
            $t->decimal('qty', 18, 6);                         // positive numbers only
            $t->decimal('unit_cost', 18, 6)->nullable();       // optional valuation
            $t->unsignedBigInteger('source_bom_id')->nullable();// who lent (for traceability)
            $t->morphs('ref');                                 // ref_type, ref_id (stock_entries, issues, etc.)
            $t->text('note')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();

            $t->index(['bom_id','product_variant_id','created_at']);
            $t->index(['ref_type','ref_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('bom_deficit_transactions'); }
};
