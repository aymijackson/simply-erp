<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('hr_contracts')) {
            return;
        }

        Schema::create('hr_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('job_position_id')->nullable()->constrained('hr_job_positions')->nullOnDelete();
            $table->foreignId('job_grade_id')->nullable()->constrained('hr_job_grades')->nullOnDelete();
            $table->string('contract_type'); // permanent, fixed_term, probation, part_time, contract
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->string('currency_code')->nullable();
            $table->string('pay_frequency')->nullable(); // monthly, weekly, biweekly
            $table->string('status')->default('active'); // active, expired, terminated
            $table->date('termination_date')->nullable();
            $table->string('termination_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_contracts');
    }
};
