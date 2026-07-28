<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_supplier_credit_applications')) {
            return;
        }

        Schema::create('finance_supplier_credit_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_credit_id')->constrained('finance_supplier_credits')->onDelete('cascade');
            $table->foreignId('bill_id')->constrained('finance_supplier_bills')->onDelete('cascade');
            $table->decimal('amount_applied', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_supplier_credit_applications');
    }
};
