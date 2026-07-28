<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('project_budgets')) {
            return;
        }

        Schema::create('project_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->string('budget_code')->nullable();
            $table->string('budget_name');
            $table->unsignedInteger('version_no')->default(1);
            $table->date('budget_start_date')->nullable();
            $table->date('budget_end_date')->nullable();
            $table->string('currency_code')->nullable();
            $table->decimal('total_budget_amount', 15, 2)->default(0);
            $table->string('status')->default('draft'); // draft, approved, revised, closed
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_budgets');
    }
};
