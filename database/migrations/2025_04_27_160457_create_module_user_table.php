<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('module_user', function (Blueprint $table) {
            // FK to modules.id
            $table->foreignId('module_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // FK to users.id
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // Composite primary key
            $table->primary(['module_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_user');
    }
};
