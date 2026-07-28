<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('hr_leave_types')) {
            return;
        }

        Schema::create('hr_leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('name');
            $table->string('code')->nullable();
            $table->decimal('days_allowed', 6, 2)->default(0);
            $table->decimal('carry_over_days', 6, 2)->default(0);
            $table->boolean('is_paid')->default(true);
            $table->boolean('requires_approval')->default(true);
            $table->string('gender_restriction')->nullable(); // male, female, null = none
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_leave_types');
    }
};
