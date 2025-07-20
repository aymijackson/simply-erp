<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->string('subject')->nullable();
            $table->text('content');
            $table->unsignedBigInteger('employee_id')->nullable(); // author
            $table->string('notable_type')->nullable(); // for polymorphic relation (e.g. Lead, Opportunity, Customer)
            $table->unsignedBigInteger('notable_id')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notes');
    }
};