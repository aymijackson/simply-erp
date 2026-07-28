<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('support_tickets')) {
            return;
        }

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_no')->unique();
            $table->string('subject');
            $table->text('description')->nullable();
            $table->enum('status', ['open', 'pending', 'resolved', 'closed'])->default('open');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('channel', ['web', 'email', 'phone', 'whatsapp', 'other'])->nullable();
            $table->enum('category', ['billing', 'technical', 'account', 'sales', 'other'])->nullable();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
