<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('vehicles')) {
            return;
        }

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('registration_no')->nullable();
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->string('color')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('vin')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
