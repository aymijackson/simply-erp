<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('project_budget_lines')) {
            return;
        }

        Schema::create('project_budget_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('project_budget_id')->constrained('project_budgets')->onDelete('cascade');
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('task_id')->nullable()->constrained('project_tasks')->nullOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained('project_milestones')->nullOnDelete();
            $table->string('cost_category')->nullable();
            $table->string('line_description')->nullable();
            $table->decimal('quantity', 15, 2)->nullable();
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->decimal('budget_amount', 15, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_budget_lines');
    }
};
