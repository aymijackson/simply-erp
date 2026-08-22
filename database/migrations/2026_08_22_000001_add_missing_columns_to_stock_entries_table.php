<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stock_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_entries', 'document_no')) {
                $table->string('document_no')->nullable()->after('id');
            }
            if (!Schema::hasColumn('stock_entries', 'reference_type')) {
                $table->string('reference_type')->nullable()->after('reference');
            }
            if (!Schema::hasColumn('stock_entries', 'reference_id')) {
                $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');
            }
            if (!Schema::hasColumn('stock_entries', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('posted_by')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('stock_entries', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('stock_entries', 'posted_at')) {
                $table->timestamp('posted_at')->nullable()->after('posted_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_entries', function (Blueprint $table) {
            $table->dropColumn(['document_no', 'reference_type', 'reference_id', 'posted_at']);
            $table->dropConstrainedForeignId('approved_by');
        });
    }
};
