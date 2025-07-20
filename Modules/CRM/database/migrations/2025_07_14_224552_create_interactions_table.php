<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('interactions', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->text('details')->nullable();
            $table->enum('interaction_type', ['call', 'email', 'meeting', 'message', 'other'])->default('other');
            $table->dateTime('interaction_date');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade'); // owner
            $table->string('interactable_type'); // Polymorphic relation: Customer, Lead, Opportunity
            $table->unsignedBigInteger('interactable_id');
            $table->timestamps();

            $table->index(['interactable_type', 'interactable_id'], 'interactable_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('interactions');
    }
};
// This migration creates the 'interactions' table with fields for subject, details, interaction type, date, and a polymorphic relation to interactable entities like Customer, Lead, or Opportunity. It also includes a foreign key to the employees table for the owner of the interaction. The interaction type is an enum with predefined values. The table is indexed on the interactable type and ID for efficient querying.