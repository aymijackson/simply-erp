<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('hr_payslip_lines')) {
            return;
        }

        Schema::create('hr_payslip_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_id')->constrained('hr_payslips')->onDelete('cascade');
            $table->string('type'); // allowance, deduction, statutory
            $table->string('code')->nullable();
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->unsignedBigInteger('gl_account_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_payslip_lines');
    }
};
