<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('hr_rosters')) {
            return;
        }

        Schema::create('hr_rosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('shift_id')->constrained('hr_shifts')->onDelete('cascade');
            $table->date('roster_date');
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'roster_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_rosters');
    }
};
