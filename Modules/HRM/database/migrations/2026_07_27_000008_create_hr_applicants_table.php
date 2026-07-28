<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('hr_applicants')) {
            return;
        }

        Schema::create('hr_applicants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_opening_id')->constrained('hr_job_openings')->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('cv_path')->nullable();
            $table->string('source')->nullable();
            $table->string('referral_name')->nullable();
            $table->string('stage')->default('applied'); // applied, screening, interview, offer, hired, rejected
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('hired_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_applicants');
    }
};
