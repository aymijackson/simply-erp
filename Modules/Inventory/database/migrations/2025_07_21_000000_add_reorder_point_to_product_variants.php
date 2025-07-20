<?php

// database/migrations/2025_07_21_000000_add_reorder_point_to_product_variants.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('product_variants', function (Blueprint $t) {
            $t->unsignedInteger('reorder_point')->default(0)->after('stock_quantity');
        });
    }
    public function down()
    {
        Schema::table('product_variants', fn (Blueprint $t) => $t->dropColumn('reorder_point'));
    }
};
