<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('notes')) {
            return;
        }

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->string('subject')->nullable();
            $table->text('content');
            $table->foreignId('author_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->nullableMorphs('notable'); // notable_type & notable_id
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
