<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SinCatalogItem;
use App\Models\User;
use App\Services\Siat\SiatCatalogRegistry;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;
use Yajra\DataTables\Facades\DataTables;

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

    public function siatCatalogItems(Request $request, string $catalog): JsonResponse
    {
        abort_unless(auth()->user()?->can('siat-catalogs.view'), 403);

        app(SiatCatalogRegistry::class)->find($catalog);

        $query = SinCatalogItem::query()
            ->where('catalog_key', $catalog)
            ->select('sin_catalog_items.*');

        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request): void {
                $keyword = trim((string) data_get($request->input('search'), 'value', ''));

                if ($keyword === '') {
                    return;
                }

                $query->where(function ($query) use ($keyword): void {
                    $query->where('classifier_code', 'ilike', "%{$keyword}%")
                        ->orWhere('item_key', 'ilike', "%{$keyword}%")
                        ->orWhere('description', 'ilike', "%{$keyword}%")
                        ->orWhereRaw('raw_data::text ilike ?', ["%{$keyword}%"]);
                });
            })
            ->addColumn('selector', fn (SinCatalogItem $item): string => $this->catalogItemSelector($item))
            ->addColumn('status', fn (SinCatalogItem $item): string => $this->catalogItemStatus($catalog, $item))
            ->addColumn('code', fn (SinCatalogItem $item): string => e($item->classifier_code ?: $item->item_key ?: '-'))
            ->editColumn('description', fn (SinCatalogItem $item): string => e($item->description ?: '-'))
            ->addColumn('raw_fields', fn (SinCatalogItem $item): string => $this->catalogRawFields($item))
            ->editColumn('synced_at', fn (SinCatalogItem $item): string => $item->synced_at?->format('Y-m-d H:i:s') ?? '')
            ->addColumn('json', fn (SinCatalogItem $item): string => $this->catalogJson($item))
            ->rawColumns(['selector', 'status', 'raw_fields', 'json'])
            ->toJson();
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

    private function catalogItemSelector(SinCatalogItem $item): string
    {
        return '<input class="form-check-input" form="catalog-selected-status-form" name="items[]" type="checkbox" value="'
            .e((string) $item->id)
            .'" aria-label="Seleccionar item '
            .e($item->classifier_code ?: $item->item_key)
            .'" data-catalog-item-selector>';
    }

    private function catalogItemStatus(string $catalog, SinCatalogItem $item): string
    {
        $buttonClass = $item->is_active ? 'btn-success' : 'btn-outline-secondary';
        $label = $item->is_active ? 'Activo' : 'Inactivo';

        return '<form method="POST" action="'
            .e(route('siat.catalogs.items.update-status', [$catalog, $item]))
            .'">'
            .csrf_field()
            .method_field('PATCH')
            .'<input type="hidden" name="is_active" value="'.($item->is_active ? '0' : '1').'">'
            .'<button class="btn btn-sm '.$buttonClass.'" type="submit">'.$label.'</button>'
            .'</form>';
    }

    private function catalogRawFields(SinCatalogItem $item): string
    {
        $fields = collect($item->raw_data ?? [])
            ->filter(fn ($value): bool => is_scalar($value) && trim((string) $value) !== '');

        if ($fields->isEmpty()) {
            return '<span class="text-body-secondary">-</span>';
        }

        return $fields
            ->map(fn ($value, string|int $key): string => '<div class="small"><span class="text-body-secondary">'
                .e((string) $key)
                .':</span> <span class="fw-semibold">'
                .e((string) $value)
                .'</span></div>')
            ->implode('');
    }

    private function catalogJson(SinCatalogItem $item): string
    {
        $json = json_encode($item->raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return '<details><summary class="text-primary">Ver</summary><pre class="mt-2 mb-0 p-2 bg-muted-lt rounded text-secondary small">'
            .e((string) $json)
            .'</pre></details>';
    }
}
