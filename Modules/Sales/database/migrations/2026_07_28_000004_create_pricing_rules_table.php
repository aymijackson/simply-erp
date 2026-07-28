<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pricing_rules')) {
            return;
        }

        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('name');
            $table->string('apply_on'); // all, product, category, customer, price_list
            $table->unsignedBigInteger('apply_to_id')->nullable();
            $table->string('discount_type'); // percent, fixed
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->decimal('min_order_qty', 15, 4)->nullable();
            $table->decimal('min_order_amount', 15, 2)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
