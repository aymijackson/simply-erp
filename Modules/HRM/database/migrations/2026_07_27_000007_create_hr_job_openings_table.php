<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('hr_job_openings')) {
            return;
        }

        Schema::create('hr_job_openings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('job_position_id')->nullable()->constrained('hr_job_positions')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->unsignedInteger('vacancies')->default(1);
            $table->date('posted_date')->nullable();
            $table->date('closing_date')->nullable();
            $table->string('status')->default('open'); // open, on_hold, closed, cancelled
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_job_openings');
    }
};
