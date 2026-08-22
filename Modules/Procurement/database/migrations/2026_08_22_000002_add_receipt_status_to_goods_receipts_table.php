<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('proc_goods_receipts', 'receipt_status')) {
            return;
        }

        Schema::table('proc_goods_receipts', function (Blueprint $table) {
            // partial | complete - null until the GRN is actually received (set by
            // GoodsReceiptController::determineGoodsReceiptStatus()), distinct from
            // `status` (draft/posted/cancelled, the document's own lifecycle state).
            $table->string('receipt_status')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('proc_goods_receipts', function (Blueprint $table) {
            $table->dropColumn('receipt_status');
        });
    }
};
