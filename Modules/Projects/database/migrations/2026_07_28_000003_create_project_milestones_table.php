<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('project_milestones')) {
            return;
        }

        Schema::create('project_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->string('milestone_code')->nullable();
            $table->string('milestone_name');
            $table->text('description')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending'); // pending, in_progress, completed, cancelled
            $table->string('priority')->default('normal');
            $table->date('target_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->decimal('weight_percent', 5, 2)->nullable();
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
        Schema::dropIfExists('project_milestones');
    }
};
