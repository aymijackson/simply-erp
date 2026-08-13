<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('finance_bank_accounts') || Schema::hasColumn('finance_bank_accounts', 'is_default')) {
            return;
        }

        Schema::table('finance_bank_accounts', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('finance_bank_accounts', 'is_default')) {
            Schema::table('finance_bank_accounts', function (Blueprint $table) {
                $table->dropColumn('is_default');
            });
        }
    }
};
