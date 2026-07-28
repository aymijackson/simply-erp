<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('bom_headers')) {
            return;
        }

        /* --- A. product_variants: add item_type -------------------- */
        if (!Schema::hasColumn('product_variants', 'item_type')) {
            Schema::table('product_variants', function (Blueprint $t) {
                $t->enum('item_type',
                         ['raw','wip','fg','tool','service'])
                  ->default('raw')
                  ->after('sku');
            });
        }

        /* --- B. BOM header ---------------------------------------- */
        Schema::create('bom_headers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_variant_id')->constrained();
            $t->string('name')->unique();
            $t->string('bom_code')->unique();
            $t->text('description')->nullable();
            $t->decimal('yield_qty', 15, 4)->default(1);   // how many FG units this BOM makes
            $t->enum('status',['draft','approved'])->default('draft');
            $t->timestamps();
        });

        /* --- C. BOM items (many raw → one FG) --------------------- */
        Schema::create('bom_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('bom_header_id')->constrained('bom_headers')
              ->cascadeOnDelete();
            $t->foreignId('product_variant_id')->constrained('product_variants');
            $t->decimal('qty_per_parent', 15, 4);          // e.g. 2 screws per 1 table
            $t->decimal('unit_cost', 15, 4)->nullable();
            $t->timestamps();
            $t->unique(['bom_header_id','product_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom_items');
        Schema::dropIfExists('bom_headers');
        Schema::table('product_variants', fn (Blueprint $t) => $t->dropColumn('item_type'));
    }
};
