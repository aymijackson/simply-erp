<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales_deliveries')) {
            return;
        }

        Schema::table('sales_deliveries', function (Blueprint $table) {
            if (Schema::hasColumn('sales_deliveries', 'driver_id') && Schema::hasTable('drivers') && !$this->hasForeign('sales_deliveries', 'sales_deliveries_driver_id_foreign')) {
                $table->foreign('driver_id')->references('id')->on('drivers')->nullOnDelete();
            }
            if (Schema::hasColumn('sales_deliveries', 'vehicle_id') && Schema::hasTable('vehicles') && !$this->hasForeign('sales_deliveries', 'sales_deliveries_vehicle_id_foreign')) {
                $table->foreign('vehicle_id')->references('id')->on('vehicles')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_deliveries', function (Blueprint $table) {
            if ($this->hasForeign('sales_deliveries', 'sales_deliveries_driver_id_foreign')) {
                $table->dropForeign('sales_deliveries_driver_id_foreign');
            }
            if ($this->hasForeign('sales_deliveries', 'sales_deliveries_vehicle_id_foreign')) {
                $table->dropForeign('sales_deliveries_vehicle_id_foreign');
            }
        });
    }

    private function hasForeign(string $table, string $constraint): bool
    {
        $conn = Schema::getConnection();

        return $conn->table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', $conn->getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->exists();
    }
};
