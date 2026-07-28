<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('proc_purchase_orders')) {
            return;
        }

        Schema::create('proc_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('purchase_requisition_id')->nullable()->constrained('proc_purchase_requisitions')->nullOnDelete();
            $table->foreignId('rfq_id')->nullable()->constrained('proc_request_for_quotations')->nullOnDelete();
            $table->foreignId('supplier_quotation_id')->nullable()->constrained('proc_supplier_quotations')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('supplier_contact_id')->nullable()->constrained('supplier_contacts')->nullOnDelete();
            $table->string('po_no')->nullable();
            $table->string('supplier_po_ref')->nullable();
            $table->date('po_date');
            $table->date('expected_delivery_date')->nullable();
            $table->string('currency_code')->nullable();
            $table->decimal('fx_rate', 18, 6)->nullable();
            $table->foreignId('delivery_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('delivery_store_id')->nullable()->constrained('location_stores')->nullOnDelete();
            $table->foreignId('bill_to_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('payment_terms')->nullable();
            $table->string('incoterms')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('shipping_total', 15, 2)->default(0);
            $table->decimal('other_charges_total', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('received_amount', 15, 2)->default(0);
            $table->decimal('billed_amount', 15, 2)->default(0);
            $table->string('status')->default('draft'); // draft, approved, issued, partially_rcv, fully_rcv, closed, cancelled
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'po_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proc_purchase_orders');
    }
};
