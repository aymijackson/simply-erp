<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // database/migrations/2025_08_01_0001_alter_stock_entries_add_src.php
        Schema::table('stock_entries', function (Blueprint $t) {
            $t->enum('entry_type', ['normal','cust_return'])
            ->default('normal')->after('status');
        });

        Schema::table('stock_issues', function (Blueprint $t) {
            $t->enum('issue_type', ['normal','supp_return'])
            ->default('normal')->after('status');
        });
    }
    
    public function down(): void
    {
        Schema::table('stock_entries', function (Blueprint $t) {
            $t->dropColumn('entry_type');
        });

        Schema::table('stock_issues', function (Blueprint $t) {
            $t->dropColumn('issue_type');
        });
    }   
};