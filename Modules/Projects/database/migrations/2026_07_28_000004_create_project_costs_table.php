<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('project_costs')) {
            return;
        }

        Schema::create('project_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('task_id')->nullable()->constrained('project_tasks')->nullOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained('project_milestones')->nullOnDelete();
            $table->date('cost_date');
            $table->string('cost_category')->nullable(); // labor, material, equipment, subcontract, overhead, other
            $table->string('source_type')->nullable(); // polymorphic-ish tag for originating record
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('description')->nullable();
            $table->decimal('quantity', 15, 2)->nullable();
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('currency_code')->nullable();
            $table->string('status')->default('posted'); // draft, posted, void
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_costs');
    }
};
