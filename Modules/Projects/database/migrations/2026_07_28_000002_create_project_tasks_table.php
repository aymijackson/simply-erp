<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('project_tasks')) {
            return;
        }

        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('parent_task_id')->nullable()->constrained('project_tasks')->nullOnDelete();
            $table->string('task_code')->nullable();
            $table->string('task_name');
            $table->text('description')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending'); // pending, in_progress, blocked, completed, cancelled
            $table->string('priority')->default('normal');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('estimated_hours', 10, 2)->nullable();
            $table->decimal('actual_hours', 10, 2)->default(0);
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
    }
};
