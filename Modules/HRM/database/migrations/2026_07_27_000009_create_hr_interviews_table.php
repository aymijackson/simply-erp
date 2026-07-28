<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('hr_interviews')) {
            return;
        }

        Schema::create('hr_interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('hr_applicants')->onDelete('cascade');
            $table->foreignId('interviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('scheduled_at');
            $table->string('type')->nullable(); // phone, in_person, video
            $table->string('outcome')->nullable(); // pending, passed, failed
            $table->decimal('score', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_interviews');
    }
};
