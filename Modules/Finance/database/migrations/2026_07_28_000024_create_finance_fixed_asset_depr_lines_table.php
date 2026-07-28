<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('finance_fixed_asset_depr_lines')) {
            return;
        }

        Schema::create('finance_fixed_asset_depr_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('depr_run_id')->constrained('finance_fixed_asset_depr_runs')->onDelete('cascade');
            $table->foreignId('asset_id')->constrained('finance_fixed_assets')->onDelete('cascade');
            $table->decimal('opening_nbv', 15, 2)->default(0);
            $table->decimal('depreciation_amount', 15, 2)->default(0);
            $table->decimal('closing_nbv', 15, 2)->default(0);
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_fixed_asset_depr_lines');
    }
};
