<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('currencies')) {
            return;
        }

        Schema::create('currencies', function (Blueprint $table) {
            $table->string('code', 3)->primary();
            $table->string('name');
            $table->string('symbol')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('currencies')->insert([
            ['code' => 'NGN', 'name' => 'Nigerian Naira', 'symbol' => '₦', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
