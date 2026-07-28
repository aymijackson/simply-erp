<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('document_links')) {
            return;
        }

        Schema::create('document_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->string('linkable_type');
            $table->unsignedBigInteger('linkable_id');
            $table->string('relation_type')->nullable();
            $table->string('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['linkable_type', 'linkable_id'], 'document_links_linkable_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_links');
    }
};
