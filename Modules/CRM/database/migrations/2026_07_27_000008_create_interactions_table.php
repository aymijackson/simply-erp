<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('interactions')) {
            return;
        }

        Schema::create('interactions', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->text('details')->nullable();
            $table->enum('interaction_type', ['call', 'email', 'meeting', 'message', 'visit', 'other'])->default('other');
            $table->dateTime('interaction_date');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('interactable_type');
            $table->unsignedBigInteger('interactable_id');
            $table->timestamps();

            $table->index(['interactable_type', 'interactable_id'], 'interactable_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactions');
    }
};
