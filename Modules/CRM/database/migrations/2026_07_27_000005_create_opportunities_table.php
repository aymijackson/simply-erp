<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('opportunities')) {
            return;
        }

        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->decimal('value', 15, 2)->nullable();
            $table->string('stage'); // prospecting, qualification, proposal, negotiation, won, lost
            $table->unsignedTinyInteger('probability')->nullable();
            $table->date('close_date')->nullable();
            $table->foreignId('owner_id')->constrained('employees')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
