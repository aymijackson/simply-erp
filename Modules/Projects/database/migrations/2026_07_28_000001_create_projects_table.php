<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('projects')) {
            return;
        }

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('project_code')->nullable();
            $table->string('project_name');
            $table->foreignId('client_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('project_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('planning'); // planning, active, on_hold, completed, cancelled
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('budget', 15, 2)->nullable();
            $table->decimal('actual_cost', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
