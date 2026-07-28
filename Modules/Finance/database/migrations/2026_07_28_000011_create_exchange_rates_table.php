<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('exchange_rates')) {
            return;
        }

        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('base_currency', 3);
            $table->string('quote_currency', 3);
            $table->decimal('rate', 20, 8);
            $table->date('rate_date');
            $table->string('source')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['base_currency', 'quote_currency', 'rate_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
