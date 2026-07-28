<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_account_types')) {
            return;
        }

        Schema::create('finance_account_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category'); // asset, liability, equity, income, cogs, expense
            $table->string('normal_balance'); // debit, credit
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_account_types');
    }
};
