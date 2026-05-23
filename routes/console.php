<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('companies:purge-floating-data {--force : Delete the detected records instead of only reporting counts}', function (): int {
    $force = (bool) $this->option('force');

    $this->warn($force
        ? 'Deleting floating pre-company data. This cannot be undone without a database backup.'
        : 'Dry run only. Re-run with --force to delete these records.');

    $definitions = [
        'sale_payments' => fn () => DB::table('sale_payments')
            ->whereIn('sale_id', floatingSalesQuery())
            ->orWhereIn('payment_method_id', floatingPaymentMethodsQuery()),
        'sale_details' => fn () => DB::table('sale_details')
            ->whereIn('sale_id', floatingSalesQuery())
            ->orWhereIn('product_id', floatingProductsQuery())
            ->orWhereIn('presentation_id', floatingPresentationsQuery()),
        'cash_register_expenses' => fn () => DB::table('cash_register_expenses')
            ->whereIn('cash_register_id', floatingCashRegistersQuery())
            ->orWhereIn('point_of_sale_id', floatingPointOfSalesQuery()),
        'sales' => fn () => DB::table('sales')->whereIn('id', floatingSalesQuery()),
        'purchase_details' => fn () => DB::table('purchase_details')
            ->whereIn('purchase_id', floatingPurchasesQuery())
            ->orWhereIn('product_id', floatingProductsQuery())
            ->orWhereIn('presentation_id', floatingPresentationsQuery()),
        'purchases' => fn () => DB::table('purchases')->whereIn('id', floatingPurchasesQuery()),
        'inventory_movements' => fn () => DB::table('inventory_movements')
            ->whereIn('product_id', floatingProductsQuery())
            ->orWhereIn('warehouse_id', floatingWarehousesQuery())
            ->orWhereIn('presentation_id', floatingPresentationsQuery()),
        'cash_registers' => fn () => DB::table('cash_registers')->whereIn('id', floatingCashRegistersQuery()),
        'point_of_sale_user' => fn () => DB::table('point_of_sale_user')->whereIn('point_of_sale_id', floatingPointOfSalesQuery()),
        'point_of_sales' => fn () => DB::table('point_of_sales')->whereIn('id', floatingPointOfSalesQuery()),
        'warehouses' => fn () => DB::table('warehouses')->whereIn('id', floatingWarehousesQuery()),
        'branches' => fn () => DB::table('branches')->whereNull('company_id'),
        'product_presentations' => fn () => DB::table('product_presentations')->whereIn('product_id', floatingProductsQuery()),
        'media' => fn () => DB::table('media')
            ->where('model_type', 'App\\Models\\Product')
            ->whereIn('model_id', floatingProductsQuery()),
        'products' => fn () => DB::table('products')->whereIn('id', floatingProductsQuery()),
        'categories' => fn () => DB::table('categories')->whereNull('company_id'),
        'measurement_units' => fn () => DB::table('measurement_units')->whereNull('company_id'),
        'presentations' => fn () => DB::table('presentations')->whereNull('company_id'),
        'payment_methods' => fn () => DB::table('payment_methods')->whereNull('company_id'),
        'suppliers' => fn () => DB::table('suppliers')->whereNull('company_id'),
        'customers' => fn () => DB::table('customers')->whereNull('company_id'),
    ];

    $counts = [];

    foreach ($definitions as $table => $query) {
        $counts[$table] = (clone $query())->count();
    }

    $this->table(
        ['Table', 'Floating records'],
        collect($counts)->map(fn (int $count, string $table): array => [$table, $count])->values()->all()
    );

    $total = array_sum($counts);

    if ($total === 0) {
        $this->info('No floating data found.');

        return self::SUCCESS;
    }

    if (! $force) {
        $this->comment("Total floating records detected: {$total}");

        return self::SUCCESS;
    }

    DB::transaction(function () use ($definitions, &$counts): void {
        foreach ($definitions as $table => $query) {
            if ($table === 'point_of_sale_user') {
                $counts[$table] = $query()->delete();

                continue;
            }

            $ids = $query()->pluck($table.'.id')->all();
            $counts[$table] = $ids === []
                ? 0
                : DB::table($table)->whereIn('id', $ids)->delete();
        }
    });

    $this->info("Deleted {$total} floating records.");

    return self::SUCCESS;
})->purpose('Report or delete pre-company records not assigned to any company');

if (! function_exists('floatingProductsQuery')) {
    function floatingProductsQuery()
    {
        return DB::table('products')->select('id')->whereNull('company_id');
    }
}

if (! function_exists('floatingPaymentMethodsQuery')) {
    function floatingPaymentMethodsQuery()
    {
        return DB::table('payment_methods')->select('id')->whereNull('company_id');
    }
}

if (! function_exists('floatingPresentationsQuery')) {
    function floatingPresentationsQuery()
    {
        return DB::table('presentations')->select('id')->whereNull('company_id');
    }
}

if (! function_exists('floatingWarehousesQuery')) {
    function floatingWarehousesQuery()
    {
        return DB::table('warehouses')->select('id')->whereNull('company_id');
    }
}

if (! function_exists('floatingPointOfSalesQuery')) {
    function floatingPointOfSalesQuery()
    {
        return DB::table('point_of_sales')->select('id')->whereNull('company_id');
    }
}

if (! function_exists('floatingPurchasesQuery')) {
    function floatingPurchasesQuery()
    {
        return DB::table('purchases')
            ->select('purchases.id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'purchases.warehouse_id')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->whereNull('warehouses.company_id')
            ->orWhere(fn ($query) => $query
                ->whereNotNull('purchases.supplier_id')
                ->whereNull('suppliers.company_id'));
    }
}

if (! function_exists('floatingSalesQuery')) {
    function floatingSalesQuery()
    {
        return DB::table('sales')
            ->select('sales.id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'sales.warehouse_id')
            ->leftJoin('branches', 'branches.id', '=', 'sales.branch_id')
            ->leftJoin('point_of_sales', 'point_of_sales.id', '=', 'sales.point_of_sale_id')
            ->leftJoin('customers', 'customers.id', '=', 'sales.customer_id')
            ->whereNull('warehouses.company_id')
            ->orWhereNull('branches.company_id')
            ->orWhere(fn ($query) => $query
                ->whereNotNull('sales.point_of_sale_id')
                ->whereNull('point_of_sales.company_id'))
            ->orWhere(fn ($query) => $query
                ->whereNotNull('sales.customer_id')
                ->whereNull('customers.company_id'));
    }
}

if (! function_exists('floatingCashRegistersQuery')) {
    function floatingCashRegistersQuery()
    {
        return DB::table('cash_registers')
            ->select('cash_registers.id')
            ->leftJoin('branches', 'branches.id', '=', 'cash_registers.branch_id')
            ->leftJoin('point_of_sales', 'point_of_sales.id', '=', 'cash_registers.point_of_sale_id')
            ->whereNull('branches.company_id')
            ->orWhere(fn ($query) => $query
                ->whereNotNull('cash_registers.point_of_sale_id')
                ->whereNull('point_of_sales.company_id'));
    }
}
