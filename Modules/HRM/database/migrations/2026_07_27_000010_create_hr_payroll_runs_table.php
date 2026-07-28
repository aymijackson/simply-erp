<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('hr_payroll_runs')) {
            return;
        }

        Schema::create('hr_payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('run_no')->nullable();
            $table->unsignedTinyInteger('period_month');
            $table->unsignedSmallInteger('period_year');
            $table->date('pay_date')->nullable();
            $table->string('status')->default('draft'); // draft, approved, posted, cancelled
            $table->decimal('total_gross', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('total_net', 15, 2)->default(0);
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'period_month', 'period_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_payroll_runs');
    }
};
