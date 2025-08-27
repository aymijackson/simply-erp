<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('work_order_task_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_task_id', 'fk_wota_wot')->constrained('work_order_tasks')->cascadeOnDelete();
            $table->foreignId('employee_id', 'fk_wota_step')->constrained('employees')->cascadeOnDelete();
            $table->string('role')->default('worker');
            $table->dateTime('assigned_at')->useCurrent();
            $table->timestamps();
            $table->unique(['work_order_task_id','employee_id'], 'uq_wota_task_emp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_task_assignments');
    }
};
