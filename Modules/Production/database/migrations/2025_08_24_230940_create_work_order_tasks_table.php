<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up(): void {
        Schema::create('work_order_tasks', function (Blueprint $table) 
        {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->foreignId('work_order_step_id')->nullable()->constrained('work_order_steps')->nullOnDelete();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->unsignedInteger('sequence_index')->default(0);
            $table->string('status')->default('pending'); // pending, in_progress, paused, blocked, completed, cancelled
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->unsignedInteger('estimated_minutes')->nullable();
            $table->unsignedInteger('actual_minutes')->default(0);
            $table->dateTime('due_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['work_order_id','sequence_index'], 'uq_wota_task_emp');
            $table->index(['status','priority']);
        });
    }


    public function down(): void {
        Schema::dropIfExists('work_order_tasks');
    }
};