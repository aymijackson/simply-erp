<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('project_invoice_lines')) {
            return;
        }

        Schema::create('project_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('project_invoice_id')->constrained('project_invoices')->onDelete('cascade');
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('task_id')->nullable()->constrained('project_tasks')->nullOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained('project_milestones')->nullOnDelete();
            $table->foreignId('timesheet_id')->nullable()->constrained('project_timesheets')->nullOnDelete();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('description')->nullable();
            $table->decimal('quantity', 15, 2)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('tax_rate', 8, 4)->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_invoice_lines');
    }
};
