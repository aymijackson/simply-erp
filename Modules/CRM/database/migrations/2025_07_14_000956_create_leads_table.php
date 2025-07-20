<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('lead_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('set null');
            $table->string('position')->nullable();
            $table->string('source')->nullable(); // e.g., website, referral, ad
            $table->string('status')->default('new'); // new, contacted, converted, lost
            $table->text('notes')->nullable();
            $table->timestamp('follow_up_date')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('employees')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('leads');
    }
};
