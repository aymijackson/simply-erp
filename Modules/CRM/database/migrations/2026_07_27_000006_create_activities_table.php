<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('activities')) {
            return;
        }

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->text('description')->nullable();
            $table->enum('activity_type', ['call', 'meeting', 'email', 'task']);
            $table->dateTime('due_date');
            $table->enum('status', ['pending', 'completed', 'overdue'])->default('pending');
            $table->foreignId('owner_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('status_changed_at')->nullable();
            $table->nullableMorphs('related'); // related_type & related_id
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
