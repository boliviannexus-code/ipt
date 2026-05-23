<?php

use App\Http\Controllers\Web\AdminDataTableController;
use App\Http\Controllers\Web\AuditController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\BranchController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\CompanyController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\InventoryMovementController;
use App\Http\Controllers\Web\KardexController;
use App\Http\Controllers\Web\MeasurementUnitController;
use App\Http\Controllers\Web\PaymentMethodController;
use App\Http\Controllers\Web\PermissionController;
use App\Http\Controllers\Web\PointOfSaleController;
use App\Http\Controllers\Web\PosController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\ProductPresentationController;
use App\Http\Controllers\Web\PurchaseController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\RoleController;
use App\Http\Controllers\Web\SaleController;
use App\Http\Controllers\Web\SupplierController;
use App\Http\Controllers\Web\UserController;
use App\Http\Controllers\Web\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('audits', [AuditController::class, 'index'])->middleware('permission:audits.view')->name('audits.index');
    Route::get('audits/{audit}', [AuditController::class, 'show'])->middleware('permission:audits.view')->name('audits.show');
    Route::prefix('companies')->name('companies.')->group(function (): void {
        Route::get('/', [CompanyController::class, 'index'])->middleware('permission:companies.view')->name('index');
        Route::get('create', [CompanyController::class, 'create'])->middleware('permission:companies.create')->name('create');
        Route::post('/', [CompanyController::class, 'store'])->middleware('permission:companies.create')->name('store');
        Route::get('{company}', [CompanyController::class, 'show'])->middleware('permission:companies.view')->name('show');
        Route::get('{company}/edit', [CompanyController::class, 'edit'])->middleware('permission:companies.update')->name('edit');
        Route::put('{company}', [CompanyController::class, 'update'])->middleware('permission:companies.update')->name('update');
        Route::delete('{company}', [CompanyController::class, 'destroy'])->middleware('permission:companies.delete')->name('destroy');
    });
    Route::resource('branches', BranchController::class);
    Route::resource('warehouses', WarehouseController::class);
    Route::resource('point-of-sales', PointOfSaleController::class);
    Route::get('pos', [PosController::class, 'index'])->middleware('permission:pos.access')->name('pos.index');
    Route::post('pos/open', [PosController::class, 'open'])->middleware('permission:pos.access')->name('pos.open');
    Route::post('pos/close', [PosController::class, 'close'])->middleware('permission:pos.access')->name('pos.close');
    Route::post('pos/sales', [PosController::class, 'sale'])->middleware('permission:pos.access')->name('pos.sales.store');
    Route::post('pos/expenses', [PosController::class, 'expense'])->middleware('permission:pos.access')->name('pos.expenses.store');
    Route::get('inventory', [InventoryMovementController::class, 'index'])->middleware('permission:inventory.view')->name('inventory.index');
    Route::get('inventory/transfers/create', [InventoryMovementController::class, 'createTransfer'])->middleware('permission:inventory.movements')->name('inventory.transfers.create');
    Route::post('inventory/transfers', [InventoryMovementController::class, 'storeTransfer'])->middleware('permission:inventory.movements')->name('inventory.transfers.store');
    Route::get('inventory/adjustment', [InventoryMovementController::class, 'adjustment'])->middleware('permission:inventory.movements')->name('inventory.adjustment');
    Route::post('inventory/adjustment', [InventoryMovementController::class, 'storeAdjustment'])->middleware('permission:inventory.movements')->name('inventory.adjustment.store');
    Route::get('inventory/defragment', [InventoryMovementController::class, 'defragment'])->middleware('permission:inventory.movements')->name('inventory.defragment');
    Route::post('inventory/defragment', [InventoryMovementController::class, 'storeDefragmentation'])->middleware('permission:inventory.movements')->name('inventory.defragment.store');
    Route::get('inventory/kardex', [KardexController::class, 'index'])->middleware('permission:inventory.view')->name('inventory.kardex');
    Route::get('inventory/kardex/{product}', [KardexController::class, 'show'])->middleware('permission:inventory.view')->name('inventory.kardex.show');
    Route::resource('suppliers', SupplierController::class);
    Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('purchases/{purchase}/void', [PurchaseController::class, 'void'])->middleware('permission:purchases.void')->name('purchases.void');
    Route::get('sales', [SaleController::class, 'index'])->middleware('permission:sales.view')->name('sales.index');
    Route::get('sales/cash-registers/{cashRegister}', [SaleController::class, 'show'])->middleware('permission:sales.view')->name('sales.cash-registers.show');
    Route::post('sales/{sale}/void', [SaleController::class, 'void'])->middleware('permission:sales.void')->name('sales.void');
    Route::get('reports', [ReportController::class, 'index'])->middleware('permission:reports.view')->name('reports.index');
    Route::get('reports/print', [ReportController::class, 'print'])->middleware('permission:reports.view')->name('reports.print');
    Route::prefix('datatables')->name('datatables.')->group(function (): void {
        Route::get('products', [AdminDataTableController::class, 'products'])->name('products');
        Route::get('product-presentations', [AdminDataTableController::class, 'productPresentations'])->name('product-presentations');
        Route::get('categories', [AdminDataTableController::class, 'categories'])->name('categories');
        Route::get('suppliers', [AdminDataTableController::class, 'suppliers'])->name('suppliers');
        Route::get('purchases', [AdminDataTableController::class, 'purchases'])->name('purchases');
        Route::get('sales', [AdminDataTableController::class, 'sales'])->name('sales');
        Route::get('audits', [AdminDataTableController::class, 'audits'])->name('audits');
        Route::get('stock', [AdminDataTableController::class, 'stock'])->name('stock');
        Route::get('kardex', [AdminDataTableController::class, 'kardex'])->name('kardex');
        Route::get('measurement-units', [AdminDataTableController::class, 'measurementUnits'])->name('measurement-units');
        Route::get('payment-methods', [AdminDataTableController::class, 'paymentMethods'])->name('payment-methods');
    });
    Route::resource('categories', CategoryController::class);
    Route::resource('measurement-units', MeasurementUnitController::class);
    Route::resource('payment-methods', PaymentMethodController::class);
    Route::resource('product-presentations', ProductPresentationController::class);
    Route::resource('products', ProductController::class);
    Route::prefix('users')->name('users.')->group(function (): void {
        Route::get('/', [UserController::class, 'index'])->middleware('permission:users.view')->name('index');
        Route::get('create', [UserController::class, 'create'])->middleware('permission:users.create')->name('create');
        Route::post('/', [UserController::class, 'store'])->middleware('permission:users.create')->name('store');
        Route::get('{user}', [UserController::class, 'show'])->middleware('permission:users.view')->withTrashed()->name('show');
        Route::get('{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.edit')->name('edit');
        Route::put('{user}', [UserController::class, 'update'])->middleware('permission:users.edit')->name('update');
        Route::patch('{user}/toggle-status', [UserController::class, 'toggleStatus'])->middleware('permission:users.edit')->name('toggle-status');
        Route::get('{user}/change-password', [UserController::class, 'changePasswordForm'])->middleware('permission:users.change-password')->name('change-password.form');
        Route::patch('{user}/change-password', [UserController::class, 'changePassword'])->middleware('permission:users.change-password')->name('change-password');
        Route::get('{user}/roles', [UserController::class, 'rolesForm'])->middleware('permission:users.assign-roles')->name('roles.form');
        Route::patch('{user}/assign-roles', [UserController::class, 'assignRoles'])->middleware('permission:users.assign-roles')->name('assign-roles');
        Route::delete('{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete')->name('destroy');
        Route::patch('{user}/restore', [UserController::class, 'restore'])->middleware('permission:users.restore')->name('restore');
    });
    Route::prefix('roles')->name('roles.')->group(function (): void {
        Route::get('/', [RoleController::class, 'index'])->middleware('permission:roles.view')->name('index');
        Route::get('create', [RoleController::class, 'create'])->middleware('permission:roles.create')->name('create');
        Route::post('/', [RoleController::class, 'store'])->middleware('permission:roles.create')->name('store');
        Route::get('{role}', [RoleController::class, 'show'])->middleware('permission:roles.view')->name('show');
        Route::get('{role}/edit', [RoleController::class, 'edit'])->middleware('permission:roles.edit')->name('edit');
        Route::put('{role}', [RoleController::class, 'update'])->middleware('permission:roles.edit')->name('update');
        Route::get('{role}/permissions', [RoleController::class, 'permissionsForm'])->middleware('permission:roles.assign-permissions')->name('permissions.form');
        Route::patch('{role}/permissions', [RoleController::class, 'assignPermissions'])->middleware('permission:roles.assign-permissions')->name('permissions');
        Route::delete('{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete')->name('destroy');
    });
    Route::resource('permissions', PermissionController::class)->middleware([
        'index' => 'permission:permissions.view',
        'show' => 'permission:permissions.view',
        'create' => 'permission:permissions.create',
        'store' => 'permission:permissions.create',
        'edit' => 'permission:permissions.edit',
        'update' => 'permission:permissions.edit',
        'destroy' => 'permission:permissions.delete',
    ]);
});
