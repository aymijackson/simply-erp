<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_entries', function (Blueprint $t) {
            $t->foreignId('supplier_id')
              ->nullable()
              ->constrained('suppliers')        // or companies/partners table
              ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_entries', fn (Blueprint $t) => $t->dropConstrainedForeignId('supplier_id'));
    }
};
