<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('proc_goods_receipts')) {
            return;
        }

        Schema::create('proc_goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('purchase_order_id')->constrained('proc_purchase_orders')->onDelete('cascade');
            $table->unsignedBigInteger('stock_entry_id')->nullable();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->string('grn_no')->nullable();
            $table->date('receipt_date');
            $table->string('supplier_delivery_note_no')->nullable();
            $table->foreignId('delivery_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('delivery_store_id')->nullable()->constrained('location_stores')->nullOnDelete();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->string('status')->default('draft'); // draft, posted, cancelled
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'grn_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proc_goods_receipts');
    }
};
