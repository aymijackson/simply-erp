<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_year_closes')) {
            return;
        }

        Schema::create('finance_year_closes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('fiscal_year_id')->constrained('finance_fiscal_years')->onDelete('cascade');
            $table->foreignId('closing_journal_entry_id')->nullable()->constrained('finance_journal_entries')->nullOnDelete();
            $table->decimal('net_profit', 15, 2)->default(0);
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'fiscal_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_year_closes');
    }
};
