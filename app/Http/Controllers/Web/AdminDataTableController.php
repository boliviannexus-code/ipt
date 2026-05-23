<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\AuditController;
use App\Models\Category;
use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\MeasurementUnit;
use App\Models\PaymentMethod;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use OwenIt\Auditing\Models\Audit;

class AdminDataTableController extends Controller
{
    public function audits(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('audits.view'), 403);

        $query = Audit::query()
            ->from('audits')
            ->select('audits.*', 'users.name as user_name', 'companies.name as company_name')
            ->leftJoin('users', function ($join): void {
                $join->on('users.id', '=', 'audits.user_id')
                    ->where('audits.user_type', User::class);
            })
            ->leftJoin('companies', 'companies.id', '=', 'audits.company_id')
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('audits.company_id', $companyId))
            ->when($request->filled('company_id'), fn ($query) => $query->where('audits.company_id', $request->integer('company_id')))
            ->when($request->filled('user_id'), fn ($query) => $query->where('audits.user_id', $request->integer('user_id'))->where('audits.user_type', User::class))
            ->when($request->filled('event'), fn ($query) => $query->where('audits.event', $request->string('event')))
            ->when($request->filled('auditable_type'), fn ($query) => $query->where('audits.auditable_type', $request->string('auditable_type')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('audits.created_at', '>=', $request->date('date_from')->toDateString()))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('audits.created_at', '<=', $request->date('date_to')->toDateString()));

        return DataTables::eloquent($query)
            ->editColumn('created_at', fn (Audit $audit): string => $audit->created_at?->format('Y-m-d H:i:s') ?? '')
            ->addColumn('company_name', fn (Audit $audit): string => $audit->company_name ?: 'Global')
            ->addColumn('user_name', fn (Audit $audit): string => $audit->user_name ?: 'Sistema')
            ->editColumn('event', fn (Audit $audit): string => $this->auditEventBadge((string) $audit->event))
            ->addColumn('auditable_label', fn (Audit $audit): string => AuditController::auditableLabel((string) $audit->auditable_type))
            ->addColumn('record_id', fn (Audit $audit): string => (string) $audit->auditable_id)
            ->addColumn('changes', fn (Audit $audit): string => $this->auditChangesSummary($audit))
            ->addColumn('actions', fn (Audit $audit): string => $this->auditActions((int) $audit->id))
            ->rawColumns(['event', 'actions'])
            ->toJson();
    }

    public function products(): JsonResponse
    {
        abort_unless(auth()->user()?->can('products.view'), 403);

        $query = Product::query()
            ->with('media')
            ->select('products.*', 'categories.name as category_name', 'measurement_units.name as measurement_unit_name', 'measurement_units.abbreviation as measurement_unit_abbreviation')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('measurement_units', 'measurement_units.id', '=', 'products.measurement_unit_id')
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('products.company_id', $companyId));

        return DataTables::eloquent($query)
            ->addColumn('image', fn (Product $product): string => view('products.partials.image-thumb', compact('product'))->render())
            ->editColumn('purchase_price', fn (Product $product): string => money_format_decimal($product->purchase_price))
            ->editColumn('sale_price', fn (Product $product): string => money_format_decimal($product->sale_price))
            ->editColumn('is_active', fn (Product $product): string => $this->statusBadge($product->is_active))
            ->addColumn('actions', fn (Product $product): string => view('products.partials.actions', compact('product'))->render())
            ->rawColumns(['image', 'is_active', 'actions'])
            ->toJson();
    }

    public function productPresentations(): JsonResponse
    {
        abort_unless(auth()->user()?->can('product-presentations.view'), 403);

        $query = Presentation::query()
            ->select('presentations.*')
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('presentations.company_id', $companyId));

        return DataTables::eloquent($query)
            ->editColumn('is_active', fn (Presentation $presentation): string => $this->statusBadge($presentation->is_active))
            ->addColumn('factor', fn (Presentation $presentation): string => '1 '.$presentation->name.' = '.$presentation->units_per_package.' unidades base')
            ->addColumn('actions', fn (Presentation $productPresentation): string => view('product-presentations.partials.actions', compact('productPresentation'))->render())
            ->rawColumns(['is_active', 'actions'])
            ->toJson();
    }

    public function categories(): JsonResponse
    {
        abort_unless(auth()->user()?->can('categories.view'), 403);

        $query = Category::query()
            ->select('categories.*')
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('categories.company_id', $companyId));

        return DataTables::eloquent($query)
            ->editColumn('is_active', fn (Category $category): string => $this->statusBadge($category->is_active))
            ->editColumn('created_at', fn (Category $category): string => $category->created_at?->format('Y-m-d') ?? '')
            ->addColumn('actions', fn (Category $category): string => view('categories.partials.actions', compact('category'))->render())
            ->rawColumns(['is_active', 'actions'])
            ->toJson();
    }

    public function measurementUnits(): JsonResponse
    {
        abort_unless(auth()->user()?->can('measurement-units.view'), 403);

        $query = MeasurementUnit::query()
            ->select('measurement_units.*')
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('measurement_units.company_id', $companyId));

        return DataTables::eloquent($query)
            ->editColumn('is_active', fn (MeasurementUnit $measurementUnit): string => $this->statusBadge($measurementUnit->is_active))
            ->editColumn('created_at', fn (MeasurementUnit $measurementUnit): string => $measurementUnit->created_at?->format('Y-m-d') ?? '')
            ->addColumn('actions', fn (MeasurementUnit $measurementUnit): string => view('measurement-units.partials.actions', compact('measurementUnit'))->render())
            ->rawColumns(['is_active', 'actions'])
            ->toJson();
    }

    public function paymentMethods(): JsonResponse
    {
        abort_unless(auth()->user()?->can('payment-methods.view'), 403);

        $query = PaymentMethod::query()
            ->select('payment_methods.*')
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('payment_methods.company_id', $companyId));

        return DataTables::eloquent($query)
            ->editColumn('is_active', fn (PaymentMethod $paymentMethod): string => $this->statusBadge($paymentMethod->is_active))
            ->editColumn('created_at', fn (PaymentMethod $paymentMethod): string => $paymentMethod->created_at?->format('Y-m-d') ?? '')
            ->addColumn('actions', fn (PaymentMethod $paymentMethod): string => view('payment-methods.partials.actions', compact('paymentMethod'))->render())
            ->rawColumns(['is_active', 'actions'])
            ->toJson();
    }

    public function suppliers(): JsonResponse
    {
        abort_unless(auth()->user()?->can('suppliers.view'), 403);

        $query = Supplier::query()
            ->select('suppliers.*')
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('suppliers.company_id', $companyId));

        return DataTables::eloquent($query)
            ->editColumn('is_active', fn (Supplier $supplier): string => $this->statusBadge($supplier->is_active))
            ->editColumn('created_at', fn (Supplier $supplier): string => $supplier->created_at?->format('Y-m-d') ?? '')
            ->addColumn('actions', fn (Supplier $supplier): string => view('suppliers.partials.actions', compact('supplier'))->render())
            ->rawColumns(['is_active', 'actions'])
            ->toJson();
    }

    public function purchases(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('purchases.view'), 403);

        $shouldDefaultToToday = ! $request->has('date_from') && ! $request->has('date_to');
        $dateFrom = $shouldDefaultToToday ? now()->toDateString() : $request->input('date_from');
        $dateTo = $shouldDefaultToToday ? now()->toDateString() : $request->input('date_to');

        $query = Purchase::query()
            ->select('purchases.*', 'suppliers.name as supplier_name', 'warehouses.name as warehouse_name', 'users.name as user_name')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'purchases.warehouse_id')
            ->leftJoin('users', 'users.id', '=', 'purchases.user_id')
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query
                ->where('warehouses.company_id', $companyId)
                ->where(fn ($query) => $query->whereNull('purchases.supplier_id')->orWhere('suppliers.company_id', $companyId)))
            ->when($dateFrom, fn ($query) => $query->whereDate('purchases.purchase_date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('purchases.purchase_date', '<=', $dateTo))
            ->when($request->filled('supplier_id'), fn ($query) => $query->where('purchases.supplier_id', $request->integer('supplier_id')))
            ->when($request->filled('user_id'), fn ($query) => $query->where('purchases.user_id', $request->integer('user_id')));

        return DataTables::eloquent($query)
            ->editColumn('purchase_date', fn (Purchase $purchase): string => $purchase->purchase_date?->format('Y-m-d') ?? '')
            ->editColumn('total', fn (Purchase $purchase): string => money_format_decimal($purchase->total))
            ->editColumn('status', fn (Purchase $purchase): string => $this->purchaseStatusBadge((string) $purchase->status))
            ->addColumn('actions', fn (Purchase $purchase): string => $this->purchaseActions($purchase))
            ->rawColumns(['status', 'actions'])
            ->toJson();
    }

    public function sales(): JsonResponse
    {
        abort_unless(auth()->user()?->can('sales.view'), 403);

        $query = Sale::query()
            ->select('sales.*', DB::raw('COALESCE(customers.name, sales.customer_name) as customer_name'), 'branches.name as branch_name', 'warehouses.name as warehouse_name', 'users.name as user_name')
            ->leftJoin('customers', 'customers.id', '=', 'sales.customer_id')
            ->leftJoin('branches', 'branches.id', '=', 'sales.branch_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'sales.warehouse_id')
            ->leftJoin('users', 'users.id', '=', 'sales.user_id')
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query
                ->where('warehouses.company_id', $companyId)
                ->where(fn ($query) => $query->whereNull('sales.customer_id')->orWhere('customers.company_id', $companyId)));

        return DataTables::eloquent($query)
            ->editColumn('sale_date', fn (Sale $sale): string => $sale->sale_date?->format('Y-m-d H:i') ?? '')
            ->editColumn('total', fn (Sale $sale): string => money_format_decimal($sale->total))
            ->addColumn('payments', fn (Sale $sale): string => $sale->payments()
                ->orderBy('id')
                ->get()
                ->map(fn ($payment): string => $payment->payment_method_name.' '.money_format_decimal($payment->amount))
                ->implode(' / '))
            ->toJson();
    }

    public function stock(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);

        $query = InventoryMovement::query()
            ->select([
                'products.id as product_id',
                'products.name as product_name',
                'measurement_units.abbreviation as measurement_unit_abbreviation',
                'products.is_active as product_is_active',
                'products.minimum_stock',
                'categories.id as category_id',
                'categories.name as category_name',
                'warehouses.id as warehouse_id',
                'warehouses.name as warehouse_name',
                'branches.name as branch_name',
                DB::raw('SUM(inventory_movements.quantity) as stock'),
            ])
            ->join('products', 'products.id', '=', 'inventory_movements.product_id')
            ->leftJoin('measurement_units', 'measurement_units.id', '=', 'products.measurement_unit_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->join('warehouses', 'warehouses.id', '=', 'inventory_movements.warehouse_id')
            ->leftJoin('branches', 'branches.id', '=', 'warehouses.branch_id')
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query
                ->where('warehouses.company_id', $companyId)
                ->where('products.company_id', $companyId))
            ->when($request->filled('warehouse_id'), fn ($query) => $query->where('warehouses.id', $request->integer('warehouse_id')))
            ->when($request->filled('category_id'), fn ($query) => $query->where('categories.id', $request->integer('category_id')))
            ->when($request->filled('product_id'), fn ($query) => $query->where('products.id', $request->integer('product_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('products.is_active', $request->boolean('status')))
            ->groupBy(
                'products.id',
                'products.name',
                'measurement_units.abbreviation',
                'products.is_active',
                'products.minimum_stock',
                'categories.id',
                'categories.name',
                'warehouses.id',
                'warehouses.name',
                'branches.name',
            )
            ->when($request->boolean('low_stock'), fn ($query) => $query->havingRaw('SUM(inventory_movements.quantity) <= products.minimum_stock'))
            ->havingRaw('SUM(inventory_movements.quantity) <> 0');

        return DataTables::eloquent($query)
            ->editColumn('stock', fn ($row): string => $this->stockBadge((int) $row->stock, (int) $row->minimum_stock, $row->measurement_unit_abbreviation))
            ->addColumn('presentations', fn ($row): string => $this->presentationBreakdown((int) $row->product_id, (int) $row->warehouse_id))
            ->addColumn('status', fn ($row): string => $this->statusBadge((bool) $row->product_is_active))
            ->addColumn('actions', fn ($row): string => $this->stockActions((int) $row->product_id, (int) $row->warehouse_id))
            ->rawColumns(['stock', 'presentations', 'status', 'actions'])
            ->toJson();
    }

    public function kardex(): JsonResponse
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);

        $query = InventoryMovement::query()
            ->select([
                'products.id as product_id',
                'products.name as product_name',
                'products.is_active as product_is_active',
                'measurement_units.abbreviation as measurement_unit_abbreviation',
                'categories.name as category_name',
                'warehouses.id as warehouse_id',
                'warehouses.name as warehouse_name',
                'branches.name as branch_name',
                DB::raw('COUNT(inventory_movements.id) as movements_count'),
                DB::raw('SUM(CASE WHEN inventory_movements.quantity > 0 THEN inventory_movements.quantity ELSE 0 END) as entries'),
                DB::raw('SUM(CASE WHEN inventory_movements.quantity < 0 THEN ABS(inventory_movements.quantity) ELSE 0 END) as exits'),
                DB::raw('SUM(inventory_movements.quantity) as balance'),
                DB::raw('MAX(inventory_movements.created_at) as last_movement_at'),
            ])
            ->join('products', 'products.id', '=', 'inventory_movements.product_id')
            ->leftJoin('measurement_units', 'measurement_units.id', '=', 'products.measurement_unit_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->join('warehouses', 'warehouses.id', '=', 'inventory_movements.warehouse_id')
            ->leftJoin('branches', 'branches.id', '=', 'warehouses.branch_id')
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query
                ->where('warehouses.company_id', $companyId)
                ->where('products.company_id', $companyId))
            ->when(request()->filled('warehouse_id'), fn ($query) => $query->where('warehouses.id', request()->integer('warehouse_id')))
            ->when(request()->filled('category_id'), fn ($query) => $query->where('products.category_id', request()->integer('category_id')))
            ->when(request()->filled('product_id'), fn ($query) => $query->where('products.id', request()->integer('product_id')))
            ->when(request()->filled('status'), fn ($query) => $query->where('products.is_active', request()->boolean('status')))
            ->groupBy(
                'products.id',
                'products.name',
                'products.is_active',
                'measurement_units.abbreviation',
                'categories.name',
                'warehouses.id',
                'warehouses.name',
                'branches.name',
            );

        return DataTables::eloquent($query)
            ->editColumn('last_movement_at', fn ($row): string => $row->last_movement_at ? date('Y-m-d H:i', strtotime((string) $row->last_movement_at)) : '')
            ->addColumn('status', fn ($row): string => $this->statusBadge((bool) $row->product_is_active))
            ->editColumn('entries', fn ($row): string => number_format((int) $row->entries).' '.($row->measurement_unit_abbreviation ?? 'u'))
            ->editColumn('exits', fn ($row): string => number_format((int) $row->exits).' '.($row->measurement_unit_abbreviation ?? 'u'))
            ->editColumn('balance', fn ($row): string => '<span class="badge text-bg-'.(((int) $row->balance) > 0 ? 'primary' : 'secondary').'">'.number_format((int) $row->balance).' '.($row->measurement_unit_abbreviation ?? 'u').'</span>')
            ->addColumn('actions', fn ($row): string => $this->kardexActions((int) $row->product_id, (int) $row->warehouse_id, (string) $row->product_name))
            ->rawColumns(['status', 'balance', 'actions'])
            ->toJson();
    }

    private function statusBadge(bool $active): string
    {
        return '<span class="badge text-bg-'.($active ? 'success' : 'secondary').'">'.($active ? 'Activo' : 'Inactivo').'</span>';
    }

    private function purchaseStatusBadge(string $status): string
    {
        return match ($status) {
            'voided' => '<span class="badge text-bg-danger">Anulada</span>',
            'completed' => '<span class="badge text-bg-success">Completada</span>',
            default => '<span class="badge text-bg-secondary">'.e($status).'</span>',
        };
    }

    private function purchaseActions(Purchase $purchase): string
    {
        $actions = '<div class="btn-group btn-group-sm" role="group">';
        $actions .= '<a class="btn btn-outline-secondary" href="'.route('purchases.show', $purchase).'">Ver</a>';

        if (auth()->user()?->can('purchases.void') && $purchase->status !== 'voided') {
            $actions .= '<form class="d-inline" method="POST" action="'.route('purchases.void', $purchase).'" data-confirm-void-purchase data-refresh-url="'.route('purchases.index').'">';
            $actions .= csrf_field();
            $actions .= '<button class="btn btn-outline-danger" type="submit">Anular</button>';
            $actions .= '</form>';
        }

        return $actions.'</div>';
    }

    private function auditEventBadge(string $event): string
    {
        $tone = match ($event) {
            'created' => 'success',
            'updated' => 'primary',
            'deleted' => 'danger',
            'restored' => 'info',
            default => 'secondary',
        };

        return '<span class="badge text-bg-'.$tone.'">'.AuditController::eventLabel($event).'</span>';
    }

    private function auditChangesSummary(Audit $audit): string
    {
        $old = array_keys($audit->old_values ?? []);
        $new = array_keys($audit->new_values ?? []);
        $fields = array_values(array_unique(array_merge($old, $new)));

        if ($fields === []) {
            return '-';
        }

        return collect($fields)
            ->take(4)
            ->implode(', ')
            .(count($fields) > 4 ? '...' : '');
    }

    private function auditActions(int $auditId): string
    {
        $url = route('audits.show', $auditId);

        return '<a class="btn btn-outline-primary btn-sm" href="'.$url.'" data-modal-url="'.$url.'" data-modal-title="Detalle de auditoria">Ver</a>';
    }

    private function stockBadge(int $stock, int $minimumStock, ?string $unit = null): string
    {
        $tone = $stock <= $minimumStock ? 'warning' : 'primary';
        $label = $stock.($unit ? ' '.$unit : '');

        return '<span class="badge text-bg-'.$tone.'">'.$label.'</span>';
    }

    private function presentationBreakdown(int $productId, int $warehouseId): string
    {
        $rows = InventoryMovement::query()
            ->select([
                DB::raw('COALESCE(presentation_name, "Unidad base") as presentation_name'),
                'units_per_package',
                DB::raw('SUM(package_quantity) as packages'),
                DB::raw('SUM(quantity) as units'),
            ])
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->whereNotNull('presentation_id')
            ->groupBy('presentation_name', 'units_per_package')
            ->havingRaw('SUM(package_quantity) <> 0')
            ->orderBy('presentation_name')
            ->get();

        if ($rows->isEmpty()) {
            return '<span class="text-muted">Sin presentaciones</span>';
        }

        return $rows
            ->map(fn ($row): string => '<span class="badge text-bg-light me-1 mb-1">'.(int) $row->packages.' '.$row->presentation_name.' <span class="text-muted">('.(int) $row->units.' u.)</span></span>')
            ->implode('');
    }

    private function stockActions(int $productId, int $warehouseId): string
    {
        if (! auth()->user()?->can('inventory.movements')) {
            return '';
        }

        $hasPackages = InventoryMovement::query()
            ->select('presentation_id')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('units_per_package', '>', 1)
            ->groupBy('presentation_id')
            ->havingRaw('SUM(package_quantity) > 0')
            ->exists();

        $adjustmentUrl = route('inventory.adjustment', [
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
        ]);
        $url = route('inventory.defragment', [
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
        ]);

        $actions = '<div class="btn-group btn-group-sm" role="group">';
        $actions .= '<a class="btn btn-outline-secondary" href="'.$adjustmentUrl.'" data-modal-url="'.$adjustmentUrl.'" data-modal-title="Reajustar stock">Reajustar</a>';

        if ($hasPackages) {
            $actions .= '<a class="btn btn-outline-primary" href="'.$url.'" data-modal-url="'.$url.'" data-modal-title="Desfragmentar empaque">Desfragmentar</a>';
        }

        return $actions.'</div>';
    }

    private function kardexActions(int $productId, int $warehouseId, string $productName): string
    {
        $url = route('inventory.kardex.show', [
            'product' => $productId,
            'warehouse_id' => $warehouseId,
        ]);

        return '<a class="btn btn-outline-primary btn-sm" href="'.$url.'" data-modal-url="'.$url.'" data-modal-title="Kardex - '.e($productName).'">Ver kardex</a>';
    }
}
