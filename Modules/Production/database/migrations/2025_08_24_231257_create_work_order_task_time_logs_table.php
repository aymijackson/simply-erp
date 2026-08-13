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
        if (Schema::hasTable('work_order_task_time_logs')) {
            return;
        }

        Schema::create('work_order_task_time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_task_id', 'fk_wottl_task')->constrained('work_order_tasks')->cascadeOnDelete();
            $table->foreignId('employee_id', 'fk_wottl_emp')->constrained('employees')->cascadeOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->unsignedInteger('minutes')->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['employee_id','started_at'], 'uq_wottl_task_emp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_task_time_logs');
    }
};
