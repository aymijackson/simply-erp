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
        if (Schema::hasTable('companies')) {
            return;
        }

        Schema::create('companies', function (Blueprint $table) {
            $table->id(); // Company id
            $table->string('name'); // Company name
            $table->string('email')->nullable(); // Optional contact email
            $table->string('phone')->nullable(); // Optional phone number
            $table->string('website')->nullable(); // Optional website
            $table->string('address')->nullable(); // Optional address
            $table->text('description')->nullable(); // Optional description
            $table->boolean('active')->default(true); // Company status
            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
