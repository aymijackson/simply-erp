<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_fixed_asset_maintenance')) {
            return;
        }

        Schema::create('finance_fixed_asset_maintenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('asset_id')->constrained('finance_fixed_assets')->onDelete('cascade');
            $table->foreignId('component_id')->nullable()->constrained('finance_fixed_asset_components')->nullOnDelete();
            $table->date('service_date');
            $table->string('vendor_name')->nullable();
            $table->string('reference_no')->nullable();
            $table->enum('maintenance_type', ['preventive', 'corrective', 'inspection', 'calibration', 'warranty']);
            $table->text('description')->nullable();
            $table->decimal('cost', 15, 2)->default(0);
            $table->foreignId('expense_account_id')->constrained('finance_accounts')->onDelete('restrict');
            $table->string('status')->default('draft'); // draft, posted, voided
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
        Schema::dropIfExists('finance_fixed_asset_maintenance');
    }
};
