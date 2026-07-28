<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('hr_leave_balances')) {
            return;
        }

        Schema::create('hr_leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained('hr_leave_types')->onDelete('cascade');
            $table->unsignedSmallInteger('fiscal_year');
            $table->decimal('days_entitled', 6, 2)->default(0);
            $table->decimal('days_taken', 6, 2)->default(0);
            $table->decimal('days_carried', 6, 2)->default(0);
            $table->timestamps();

            $table->unique(['employee_id', 'leave_type_id', 'fiscal_year'], 'hr_leave_balances_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_leave_balances');
    }
};
