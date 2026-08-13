<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('performances')) {
            return;
        }

        Schema::create('performances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('goal_title')->nullable(); // E.g. "Customer Satisfaction", "Sales Target"
            $table->text('kpi_description')->nullable(); // Description of specific KPI
            $table->string('review_period')->nullable(); // E.g. "Q1 2025"
            $table->decimal('score', 5, 2)->nullable(); // E.g. 87.50%
            $table->enum('rating', ['Excellent', 'Good', 'Satisfactory', 'Needs Improvement'])->nullable();
            $table->text('comments')->nullable();
            $table->date('review_date')->nullable(); // Date the review was done
            $table->foreignId('reviewed_by')->constrained('employees')->onDelete('cascade'); // Supervisor or Manager
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('performances');
    }
};
