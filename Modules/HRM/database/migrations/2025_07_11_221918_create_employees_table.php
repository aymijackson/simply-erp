<?php

// database/migrations/create_employees_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->string('employee_code')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('position')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('date_hired')->nullable();

            // verify‑email feature (nullable = not verified yet)
            $table->timestamp('email_verified_at')->nullable()
                  ->after('email');

            // hashed password
            $table->string('password')
                  ->after('email_verified_at');

            // remember‑me cookie
            $table->rememberToken()
                  ->after('password');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
