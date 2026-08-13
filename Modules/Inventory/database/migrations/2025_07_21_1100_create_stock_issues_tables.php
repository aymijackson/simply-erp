<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('stock_issues')) {
            return;
        }

        Schema::create('stock_issues', function (Blueprint $t) {
            $t->id();
            $t->string('issue_no')->unique();
            $t->foreignId('from_store_id')->constrained('location_stores');
            $t->string('reference')->nullable();              // sales order, WO, etc.
            $t->string('reason')->nullable();                 // free‑text
            $t->enum('status',['draft','posted'])->default('draft');
            $t->foreignId('requested_by')->nullable()->constrained('users');
            $t->foreignId('posted_by')->nullable()->constrained('users');
            $t->timestamp('posted_at')->nullable();
            $t->timestamps();
        });

        Schema::create('stock_issue_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stock_issue_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_variant_id')->constrained('product_variants');
            $t->decimal('qty', 14, 4);
            $t->decimal('unit_cost', 14, 4)->nullable();      // filled on post
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_issues');   
        Schema::dropIfExists('stock_issue_lines');
    }
};