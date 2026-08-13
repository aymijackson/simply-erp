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
        if (Schema::hasTable('work_order_task_time_dependencies')) {
            return;
        }

        Schema::create('work_order_task_time_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_task_id', 'fk_wottd_task')->constrained('work_order_tasks')->cascadeOnDelete();
            $table->foreignId('depends_on_task_id', 'fk_wottd_dep')->constrained('work_order_tasks')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['work_order_task_id','depends_on_task_id'], 'uq_wota_task_dep');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_task_time_dependencies');
    }
};
