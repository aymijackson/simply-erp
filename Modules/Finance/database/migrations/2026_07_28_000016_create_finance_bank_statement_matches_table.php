<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_bank_statement_matches')) {
            return;
        }

        Schema::create('finance_bank_statement_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('statement_line_id')->constrained('finance_bank_statement_lines')->onDelete('cascade');
            $table->foreignId('journal_entry_line_id')->constrained('finance_journal_entry_lines')->onDelete('cascade');
            $table->decimal('matched_amount', 15, 2)->default(0);
            $table->string('match_method')->nullable(); // auto, manual
            $table->foreignId('matched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('matched_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_bank_statement_matches');
    }
};
