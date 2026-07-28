<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_journal_entries')) {
            return;
        }

        Schema::create('finance_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('period_id')->nullable()->constrained('finance_fiscal_periods')->nullOnDelete();
            $table->string('entry_no')->nullable();
            $table->date('entry_date');
            $table->string('reference')->nullable();
            $table->text('memo')->nullable();
            $table->string('status')->default('draft'); // draft, posted, reversed
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reversal_of_id')->nullable()->constrained('finance_journal_entries')->nullOnDelete();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_journal_entries');
    }
};
