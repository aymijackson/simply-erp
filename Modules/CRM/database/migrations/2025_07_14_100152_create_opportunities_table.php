<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->decimal('value', 15, 2);
            $table->string('stage'); // e.g., prospecting, qualified, proposal, negotiation, closed-won, closed-lost
            $table->unsignedTinyInteger('probability')->nullable(); // % chance of success
            $table->date('close_date')->nullable();
            $table->foreignId('owner_id')->constrained('employees')->onDelete('cascade'); // employee responsible
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
