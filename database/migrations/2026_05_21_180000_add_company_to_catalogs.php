<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addCompany('categories', 'categories_name_unique', ['company_id', 'name']);
        $this->addCompany('products', 'products_barcode_unique', ['company_id', 'barcode']);
        $this->addCompany('suppliers');
        $this->addCompany('customers');
        $this->addCompany('measurement_units', 'measurement_units_name_unique', ['company_id', 'name']);
        $this->addUnique('measurement_units', 'measurement_units_abbreviation_unique', ['company_id', 'abbreviation']);
        $this->addCompany('presentations', 'presentations_name_unique', ['company_id', 'name']);
        $this->addCompany('payment_methods', 'payment_methods_name_unique', ['company_id', 'name']);
    }

    public function down(): void
    {
        $this->dropCompany('payment_methods', 'payment_methods_name_unique', ['company_id', 'name']);
        $this->dropCompany('presentations', 'presentations_name_unique', ['company_id', 'name']);
        $this->dropUnique('measurement_units', ['company_id', 'abbreviation']);
        $this->dropCompany('measurement_units', 'measurement_units_name_unique', ['company_id', 'name']);
        $this->dropCompany('customers');
        $this->dropCompany('suppliers');
        $this->dropCompany('products', 'products_barcode_unique', ['company_id', 'barcode']);
        $this->dropCompany('categories', 'categories_name_unique', ['company_id', 'name']);
    }

    private function addCompany(string $table, ?string $oldUnique = null, ?array $newUnique = null): void
    {
        Schema::table($table, function (Blueprint $table) use ($oldUnique, $newUnique): void {
            if ($oldUnique !== null) {
                $table->dropUnique($oldUnique);
            }

            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();

            $table->index('company_id');

            if ($newUnique !== null) {
                $table->unique($newUnique);
            }
        });
    }

    private function addUnique(string $table, string $oldUnique, array $newUnique): void
    {
        Schema::table($table, function (Blueprint $table) use ($oldUnique, $newUnique): void {
            $table->dropUnique($oldUnique);
            $table->unique($newUnique);
        });
    }

    private function dropCompany(string $table, ?string $oldUnique = null, ?array $currentUnique = null): void
    {
        Schema::table($table, function (Blueprint $table) use ($oldUnique, $currentUnique): void {
            if ($currentUnique !== null) {
                $table->dropUnique($currentUnique);
            }

            $table->dropConstrainedForeignId('company_id');

            if ($oldUnique !== null) {
                $column = str($oldUnique)->between($table->getTable().'_', '_unique')->toString();
                $table->unique($column, $oldUnique);
            }
        });
    }

    private function dropUnique(string $table, array $currentUnique): void
    {
        Schema::table($table, function (Blueprint $table) use ($currentUnique): void {
            $table->dropUnique($currentUnique);
            $table->unique('abbreviation', 'measurement_units_abbreviation_unique');
        });
    }
};
