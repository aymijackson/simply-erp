<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('proc_request_for_quotation_suppliers', 'supplier_contact_id')) {
            return;
        }

        Schema::table('proc_request_for_quotation_suppliers', function (Blueprint $table) {
            $table->foreignId('supplier_contact_id')->nullable()->after('supplier_id')
                ->constrained('supplier_contacts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('proc_request_for_quotation_suppliers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_contact_id');
        });
    }
};
