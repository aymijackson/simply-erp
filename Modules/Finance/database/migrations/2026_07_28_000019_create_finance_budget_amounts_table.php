<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_budget_amounts')) {
            return;
        }

        Schema::create('finance_budget_amounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_line_id')->constrained('finance_budget_lines')->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_budget_amounts');
    }
};
