<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('hr_payslips')) {
            return;
        }

        Schema::create('hr_payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('hr_payroll_runs')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('contract_id')->nullable()->constrained('hr_contracts')->nullOnDelete();
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('total_allowances', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);
            $table->string('status')->default('pending'); // pending, paid, cancelled
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_payslips');
    }
};
