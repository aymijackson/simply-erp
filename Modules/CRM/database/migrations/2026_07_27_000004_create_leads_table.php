<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('leads')) {
            return;
        }

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('lead_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('position')->nullable();
            $table->string('source')->nullable();
            $table->string('status')->default('new');
            $table->text('notes')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
