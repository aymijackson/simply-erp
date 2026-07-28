<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('project_timesheets')) {
            return;
        }

        Schema::create('project_timesheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('task_id')->nullable()->constrained('project_tasks')->nullOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained('project_milestones')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('entry_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('hours', 8, 2)->default(0);
            $table->decimal('hourly_rate', 15, 2)->nullable();
            $table->decimal('cost_amount', 15, 2)->default(0);
            $table->decimal('billable_hours', 8, 2)->default(0);
            $table->decimal('billing_rate', 15, 2)->nullable();
            $table->decimal('billable_amount', 15, 2)->default(0);
            $table->boolean('is_billable')->default(true);
            $table->string('status')->default('draft'); // draft, submitted, approved, rejected, invoiced
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rejection_reason')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_timesheets');
    }
};
