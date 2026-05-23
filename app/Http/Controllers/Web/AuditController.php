<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\View\View;
use OwenIt\Auditing\Models\Audit;

class AuditController extends Controller
{
    public const AUDITABLE_TYPES = [
        \App\Models\Company::class => 'Empresas',
        \App\Models\User::class => 'Usuarios',
        \App\Models\Product::class => 'Productos',
        \App\Models\Category::class => 'Categorias',
        \App\Models\Supplier::class => 'Proveedores',
        \App\Models\Customer::class => 'Clientes',
        \App\Models\Branch::class => 'Sucursales',
        \App\Models\Warehouse::class => 'Almacenes',
        \App\Models\PointOfSale::class => 'Puntos de venta',
        \App\Models\Purchase::class => 'Compras',
        \App\Models\PurchaseDetail::class => 'Detalle de compra',
        \App\Models\Sale::class => 'Ventas',
        \App\Models\SaleDetail::class => 'Detalle de venta',
        \App\Models\SalePayment::class => 'Pagos de venta',
        \App\Models\CashRegister::class => 'Cajas',
        \App\Models\CashRegisterExpense::class => 'Gastos de caja',
        \App\Models\InventoryMovement::class => 'Inventario',
        \App\Models\Presentation::class => 'Presentaciones',
        \App\Models\MeasurementUnit::class => 'Unidades de medida',
        \App\Models\PaymentMethod::class => 'Metodos de pago',
    ];

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('audits.view'), 403);

        $companyId = CompanyContext::id($request->user());

        return view('audits.index', [
            'auditableTypes' => self::AUDITABLE_TYPES,
            'companies' => Company::query()
                ->when($companyId, fn ($query, $companyId) => $query->where('id', $companyId))
                ->orderBy('name')
                ->get(['id', 'name']),
            'users' => User::query()
                ->when($companyId, fn ($query, $companyId) => $query->where('company_id', $companyId))
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, Audit $audit): View
    {
        abort_unless($request->user()?->can('audits.view'), 403);
        abort_unless($this->canSeeAudit($audit, $request->user()), 403);

        $audit->load('user');

        return view('audits.partials.show', [
            'audit' => $audit,
            'eventLabel' => self::eventLabel($audit->event),
            'auditableLabel' => self::auditableLabel((string) $audit->auditable_type),
            'oldValues' => $audit->old_values ?? [],
            'newValues' => $audit->new_values ?? [],
        ]);
    }

    public static function eventLabel(string $event): string
    {
        return match ($event) {
            'created' => 'Creado',
            'updated' => 'Editado',
            'deleted' => 'Eliminado',
            'restored' => 'Restaurado',
            default => ucfirst($event),
        };
    }

    public static function auditableLabel(string $type): string
    {
        return self::AUDITABLE_TYPES[$type] ?? class_basename($type);
    }

    private function canSeeAudit(Audit $audit, ?User $user): bool
    {
        $companyId = CompanyContext::id($user);

        return $companyId === null || (int) $audit->company_id === (int) $companyId;
    }
}
