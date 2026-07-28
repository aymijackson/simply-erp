<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('crm_customer_segment_presets')) {
            return;
        }

        Schema::create('crm_customer_segment_presets', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('description', 255)->nullable();
            $table->decimal('high_value_min', 15, 2);
            $table->unsignedInteger('hot_recency_days');
            $table->unsignedInteger('engaged_score_min');
            $table->unsignedInteger('engaged_recency_days');
            $table->unsignedInteger('dormant_days');
            $table->json('risk_statuses')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_customer_segment_presets');
    }
};
