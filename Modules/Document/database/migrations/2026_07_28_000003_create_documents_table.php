<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('documents')) {
            return;
        }

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('document_no')->nullable();
            $table->foreignId('parent_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->unsignedInteger('version_no')->default(1);
            $table->boolean('is_latest')->default(true);
            $table->foreignId('category_id')->nullable()->constrained('document_categories')->nullOnDelete();
            $table->foreignId('type_id')->nullable()->constrained('document_types')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->string('original_file_name')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_path');
            $table->string('file_disk')->default('local');
            $table->string('mime_type')->nullable();
            $table->string('file_extension')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('checksum')->nullable();
            $table->string('status')->default('active'); // active, archived, expired
            $table->string('confidentiality_level')->nullable(); // public, internal, confidential, restricted
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
