<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_fixed_asset_revaluations')) {
            return;
        }

        Schema::create('finance_fixed_asset_revaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('asset_id')->constrained('finance_fixed_assets')->onDelete('cascade');
            $table->date('reval_date');
            $table->decimal('old_cost', 15, 2)->default(0);
            $table->decimal('new_cost', 15, 2)->default(0);
            $table->decimal('delta', 15, 2)->default(0);
            $table->string('method')->nullable();
            $table->foreignId('revaluation_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->text('memo')->nullable();
            $table->string('status')->default('draft'); // draft, posted, void
            $table->foreignId('journal_entry_id')->nullable()->constrained('finance_journal_entries')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_fixed_asset_revaluations');
    }
};
