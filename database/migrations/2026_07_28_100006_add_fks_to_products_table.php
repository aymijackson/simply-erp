<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'category_id') && Schema::hasTable('categories') && !$this->hasForeign('products', 'products_category_id_foreign')) {
                $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            }
            if (Schema::hasColumn('products', 'model_id') && Schema::hasTable('models') && !$this->hasForeign('products', 'products_model_id_foreign')) {
                $table->foreign('model_id')->references('id')->on('models')->nullOnDelete();
            }
            if (Schema::hasColumn('products', 'default_uom') && Schema::hasTable('units') && !$this->hasForeign('products', 'products_default_uom_foreign')) {
                $table->foreign('default_uom')->references('id')->on('units')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if ($this->hasForeign('products', 'products_category_id_foreign')) {
                $table->dropForeign('products_category_id_foreign');
            }
            if ($this->hasForeign('products', 'products_model_id_foreign')) {
                $table->dropForeign('products_model_id_foreign');
            }
            if ($this->hasForeign('products', 'products_default_uom_foreign')) {
                $table->dropForeign('products_default_uom_foreign');
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
