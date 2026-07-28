<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('document_audits')) {
            return;
        }

        Schema::create('document_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->string('action'); // uploaded, viewed, downloaded, updated, approved, archived, deleted
            $table->string('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_audits');
    }
};
