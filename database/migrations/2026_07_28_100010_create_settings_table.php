<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            return;
        }

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('setting_group_id')->constrained('setting_groups')->cascadeOnDelete();
            $table->string('key', 120);
            $table->string('label', 160)->nullable();
            $table->string('description', 255)->nullable();

            $table->longText('value')->nullable();
            $table->enum('value_type', ['string', 'text', 'int', 'decimal', 'bool', 'json', 'date', 'datetime', 'file', 'email', 'phone', 'url'])->default('string');

            $table->enum('scope', ['global', 'company', 'location', 'user'])->default('global');
            $table->unsignedBigInteger('scope_id')->nullable();

            $table->boolean('is_sensitive')->default(false);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['scope', 'scope_id', 'key']);
            $table->index('key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
