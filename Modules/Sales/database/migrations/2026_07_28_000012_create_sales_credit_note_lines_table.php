<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('sales_credit_note_lines')) {
            return;
        }

        Schema::create('sales_credit_note_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_credit_note_id')->constrained('sales_credit_notes')->onDelete('cascade');
            $table->foreignId('sales_invoice_line_id')->nullable()->constrained('sales_invoice_lines')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('description')->nullable();
            $table->decimal('qty', 15, 4)->default(0);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('tax_rate', 8, 4)->nullable();
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('line_total', 15, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_credit_note_lines');
    }
};
