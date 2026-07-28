<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('proc_request_for_quotation_suppliers')) {
            return;
        }

        Schema::create('proc_request_for_quotation_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained('proc_request_for_quotations')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->timestamp('sent_at')->nullable();
            $table->string('response_status')->default('pending'); // pending, sent, responded, declined
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['rfq_id', 'supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proc_request_for_quotation_suppliers');
    }
};
