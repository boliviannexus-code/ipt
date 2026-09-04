<?php

namespace App\Http\Controllers\Web;

use App\Enums\InvoiceEmissionMode;
use App\Enums\InvoiceFiscalStatus;
use App\Http\Controllers\Controller;
use App\Models\AcademicModule;
use App\Models\Area;
use App\Models\Campus;
use App\Models\CommercialOrigin;
use App\Models\Company;
use App\Models\Program;
use App\Models\RectorateApplication;
use App\Models\SinCatalogItem;
use App\Models\SinInvoiceIssue;
use App\Models\User;
use App\Services\Billing\InvoiceDocumentSector;
use App\Services\Billing\PurchaseSaleInvoicePdfService;
use App\Services\Siat\SiatCatalogRegistry;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class AdminDataTableController extends Controller
{
    public function __construct(
        private readonly PurchaseSaleInvoicePdfService $invoicePdf,
    ) {}

    public function areas(): JsonResponse
    {
        abort_unless(auth()->user()?->can('areas.view'), 403);

        return DataTables::eloquent(Area::query()->select('areas.*')->withCount('positions'))
            ->editColumn('is_active', fn (Area $area): string => $this->activeBadge($area->is_active, 'Activa', 'Inactiva'))
            ->addColumn('actions', fn (Area $area): string => $this->crudActions(
                editUrl: route('areas.edit', $area),
                deleteUrl: route('areas.destroy', $area),
                label: $area->name,
                modalTitle: 'Editar área',
            ))
            ->rawColumns(['is_active', 'actions'])
            ->toJson();
    }

    public function campuses(): JsonResponse
    {
        abort_unless(auth()->user()?->can('campuses.view'), 403);

        return DataTables::eloquent(Campus::query()->select('campuses.*'))
            ->editColumn('code', fn (Campus $campus): string => '<span class="badge text-bg-secondary">'.e($campus->code).'</span>')
            ->addColumn('actions', fn (Campus $campus): string => auth()->user()?->can('campuses.manage')
                ? $this->crudActions(route('campuses.edit', $campus), route('campuses.destroy', $campus), $campus->name, 'Editar sede')
                : '')
            ->rawColumns(['code', 'actions'])
            ->toJson();
    }

    public function companies(): JsonResponse
    {
        abort_unless(auth()->user()?->can('companies.view'), 403);

        return DataTables::eloquent(Company::query()->select('companies.*')->withCount('users'))
            ->addColumn('logo', fn (Company $company): string => $company->logo_url
                ? '<img class="avatar" src="'.e($company->logo_url).'" alt="'.e($company->name).'">'
                : '<span class="avatar bg-primary-lt text-primary"><i class="ti ti-building"></i></span>')
            ->addColumn('display_name', fn (Company $company): string => '<div class="fw-semibold">'.e($company->name).'</div><div class="text-body-secondary small">'.e($company->legal_name ?: '-').'</div>')
            ->addColumn('contact', fn (Company $company): string => '<div>'.e($company->phone ?: '-').'</div><div class="text-body-secondary small">'.e($company->email ?: '-').'</div>')
            ->editColumn('is_active', fn (Company $company): string => $this->activeBadge($company->is_active))
            ->addColumn('actions', fn (Company $company): string => $this->companyActions($company))
            ->rawColumns(['logo', 'display_name', 'contact', 'is_active', 'actions'])
            ->toJson();
    }

    public function commercialOrigins(): JsonResponse
    {
        abort_unless(auth()->user()?->can('commercial-origins.view'), 403);

        return DataTables::eloquent(CommercialOrigin::query()->select('commercial_origins.*'))
            ->editColumn('created_at', fn (CommercialOrigin $origin): string => $origin->created_at?->format('d/m/Y') ?? '-')
            ->addColumn('actions', fn (CommercialOrigin $origin): string => $this->crudActions(
                auth()->user()?->can('commercial-origins.edit') ? route('parameters.commercial-origins.edit', $origin) : null,
                auth()->user()?->can('commercial-origins.delete') ? route('parameters.commercial-origins.destroy', $origin) : null,
                $origin->name,
                'Editar origen comercial',
            ))
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function programs(): JsonResponse
    {
        abort_unless(auth()->user()?->can('programs.view'), 403);

        return DataTables::eloquent(Program::query()->select('programs.*')->withCount(['plans', 'levels']))
            ->editColumn('enrollment_code', fn (Program $program): string => '<span class="badge text-bg-secondary">'.e($program->enrollment_code ?: 'Pendiente').'</span>')
            ->editColumn('duration_months', fn (Program $program): string => $program->duration_months.' meses')
            ->addColumn('actions', fn (Program $program): string => auth()->user()?->can('programs.edit')
                ? '<div class="d-inline-flex gap-1"><a class="btn btn-outline-secondary btn-sm" href="'.e(route('parameters.programs.levels.index', $program)).'"><i class="ti ti-list-numbers me-1"></i>Configurar niveles</a><a class="btn btn-outline-primary btn-sm" href="'.e(route('parameters.programs.edit', $program)).'">Editar</a></div>'
                : '')
            ->rawColumns(['enrollment_code', 'actions'])
            ->toJson();
    }

    public function permissions(): JsonResponse
    {
        abort_unless(auth()->user()?->can('permissions.view'), 403);

        return DataTables::eloquent(Permission::query()->select('permissions.*'))
            ->editColumn('name', fn (Permission $permission): string => permission_label($permission->name))
            ->editColumn('created_at', fn (Permission $permission): string => $permission->created_at?->format('Y-m-d') ?? '-')
            ->addColumn('actions', fn (Permission $permission): string => $this->permissionActions($permission))
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function roles(): JsonResponse
    {
        abort_unless(auth()->user()?->can('roles.view'), 403);

        return DataTables::eloquent(Role::query()->select('roles.*')->withCount(['users', 'permissions']))
            ->editColumn('name', fn (Role $role): string => role_label($role->name))
            ->editColumn('created_at', fn (Role $role): string => $role->created_at?->format('Y-m-d') ?? '-')
            ->addColumn('actions', fn (Role $role): string => $this->roleActions($role))
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function users(): JsonResponse
    {
        abort_unless(auth()->user()?->can('users.view'), 403);

        $query = User::query()->withTrashed()->with(['company', 'personnel', 'roles'])->select('users.*');

        return DataTables::eloquent($query)
            ->addColumn('display_name', fn (User $user): string => e($user->personnel?->full_name ?? $user->name))
            ->addColumn('company_name', fn (User $user): string => e($user->company?->name ?? 'Sin empresa'))
            ->addColumn('roles_list', fn (User $user): string => $user->roles->isEmpty()
                ? '<span class="text-body-secondary">Sin roles</span>'
                : $user->roles->map(fn (Role $role): string => '<span class="badge text-bg-primary">'.e(role_label($role->name)).'</span>')->implode(' '))
            ->addColumn('status', fn (User $user): string => $this->activeBadge($user->is_active).($user->trashed() ? ' <span class="badge text-bg-danger">Eliminado</span>' : ''))
            ->editColumn('created_at', fn (User $user): string => $user->created_at?->format('Y-m-d') ?? '-')
            ->addColumn('actions', fn (User $user): string => $this->userActions($user))
            ->filterColumn('display_name', function ($query, string $keyword): void {
                $query->where(function ($query) use ($keyword): void {
                    $query->where('users.name', 'ilike', "%{$keyword}%")
                        ->orWhereHas('personnel', fn ($query) => $query->whereRaw("concat_ws(' ', first_name, paternal_surname, maternal_surname) ilike ?", ["%{$keyword}%"]));
                });
            })
            ->filterColumn('company_name', fn ($query, string $keyword) => $query->whereHas('company', fn ($query) => $query->where('name', 'ilike', "%{$keyword}%")))
            ->rawColumns(['roles_list', 'status', 'actions'])
            ->toJson();
    }

    public function academicModules(): JsonResponse
    {
        abort_unless(auth()->user()?->can('academic-modules.view'), 403);

        $query = AcademicModule::query()
            ->with(['program', 'level', 'currentTeacherAssignment.personnel'])
            ->select('academic_modules.*');

        return DataTables::eloquent($query)
            ->addColumn('program_name', fn (AcademicModule $module): string => e($module->program?->title ?? '-'))
            ->addColumn('level_name', fn (AcademicModule $module): string => e($module->level?->name ?? '-'))
            ->addColumn('teacher_name', fn (AcademicModule $module): string => e($module->currentTeacherAssignment?->personnel?->full_name ?? 'Sin asignar'))
            ->editColumn('modality', fn (AcademicModule $module): string => '<span class="badge '.($module->modality === 'virtual' ? 'text-bg-azure' : 'text-bg-green').'">'.($module->modality === 'virtual' ? 'Virtual' : 'Presencial').'</span>')
            ->addColumn('schedule', fn (AcademicModule $module): string => e(substr((string) $module->starts_at, 0, 5).'–'.substr((string) $module->ends_at, 0, 5)))
            ->addColumn('dates', fn (AcademicModule $module): string => e($module->start_date?->format('d/m/Y').'–'.$module->end_date?->format('d/m/Y')))
            ->addColumn('actions', fn (AcademicModule $module): string => $this->academicModuleActions($module))
            ->filterColumn('program_name', fn ($query, string $keyword) => $query->whereHas('program', fn ($query) => $query->where('title', 'ilike', "%{$keyword}%")))
            ->filterColumn('level_name', fn ($query, string $keyword) => $query->whereHas('level', fn ($query) => $query->where('name', 'ilike', "%{$keyword}%")))
            ->filterColumn('teacher_name', fn ($query, string $keyword) => $query->whereHas('currentTeacherAssignment.personnel', fn ($query) => $query->whereRaw("concat_ws(' ', first_name, paternal_surname, maternal_surname) ilike ?", ["%{$keyword}%"])))
            ->rawColumns(['modality', 'actions'])
            ->toJson();
    }

    public function enrollments(): JsonResponse
    {
        abort_unless(auth()->user()?->can('rectorate.create'), 403);

        $query = RectorateApplication::query()
            ->with(['campus', 'program', 'plan', 'contract' => fn ($query) => $query->withCount('payments')->withSum('charges', 'paid_amount')])
            ->select('rectorate_applications.*');

        return DataTables::eloquent($query)
            ->addColumn('enrollment', fn (RectorateApplication $application): string => '<span class="fw-semibold d-block">'.e($application->account_number ?: 'Pendiente').'</span><small class="text-body-secondary">'.e($application->campus?->name ?? 'Sin sede').'</small>')
            ->addColumn('holder', fn (RectorateApplication $application): string => '<span class="fw-semibold d-block">'.e(trim("{$application->first_name} {$application->paternal_surname} {$application->maternal_surname}")).'</span><small class="text-body-secondary">Inscripción #'.$application->id.'</small>')
            ->addColumn('contact', fn (RectorateApplication $application): string => '<span class="d-block">'.e($application->email ?: '—').'</span><small class="text-body-secondary">'.e($application->phone ?: '—').'</small>')
            ->addColumn('program_plan', fn (RectorateApplication $application): string => '<span class="d-block">'.e($application->program?->title ?? 'Pendiente').'</span>'.($application->plan ? '<small class="text-body-secondary">'.e($application->plan->name).'</small>' : ''))
            ->addColumn('progress', fn (RectorateApplication $application): string => $this->enrollmentProgress($application))
            ->editColumn('created_at', fn (RectorateApplication $application): string => $application->created_at?->format('d/m/Y H:i') ?? '-')
            ->addColumn('actions', fn (RectorateApplication $application): string => $this->enrollmentActions($application))
            ->filterColumn('enrollment', fn ($query, string $keyword) => $query->where('account_number', 'ilike', "%{$keyword}%"))
            ->filterColumn('holder', fn ($query, string $keyword) => $query->whereRaw("concat_ws(' ', first_name, paternal_surname, maternal_surname) ilike ?", ["%{$keyword}%"]))
            ->filterColumn('program_plan', fn ($query, string $keyword) => $query->where(fn ($query) => $query->whereHas('program', fn ($query) => $query->where('title', 'ilike', "%{$keyword}%"))->orWhereHas('plan', fn ($query) => $query->where('name', 'ilike', "%{$keyword}%"))))
            ->rawColumns(['enrollment', 'holder', 'contact', 'program_plan', 'progress', 'actions'])
            ->toJson();
    }

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

    public function invoices(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('invoices.view'), 403);

        $query = SinInvoiceIssue::query()
            ->withoutGlobalScope('company')
            ->select(
                'sin_invoice_issues.*',
                'customers.name as customer_name',
                'customers.document_number as customer_document_number'
            )
            ->leftJoin('customers', 'customers.id', '=', 'sin_invoice_issues.customer_id')
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('sin_invoice_issues.company_id', $companyId));

        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request): void {
                if ($request->filled('status')) {
                    $query->where('sin_invoice_issues.fiscal_status', $request->string('status'));
                }

                $keyword = trim((string) data_get($request->input('search'), 'value', ''));

                if ($keyword === '') {
                    return;
                }

                $query->where(function ($query) use ($keyword): void {
                    $query
                        ->where('sin_invoice_issues.cuf', 'ilike', "%{$keyword}%")
                        ->orWhere('sin_invoice_issues.reception_code', 'ilike', "%{$keyword}%")
                        ->orWhere('sin_invoice_issues.status_label', 'ilike', "%{$keyword}%")
                        ->orWhere('sin_invoice_issues.fiscal_status', 'ilike', "%{$keyword}%")
                        ->orWhere('customers.name', 'ilike', "%{$keyword}%")
                        ->orWhere('customers.document_number', 'ilike', "%{$keyword}%");

                    if (is_numeric($keyword)) {
                        $query
                            ->orWhere('sin_invoice_issues.invoice_number', (int) $keyword)
                            ->orWhere('sin_invoice_issues.attempted_invoice_number', (int) $keyword);
                    }
                });
            })
            ->editColumn('invoice_number', fn (SinInvoiceIssue $invoice): string => $this->invoiceNumberColumn($invoice))
            ->addColumn('document_type', fn (SinInvoiceIssue $invoice): string => $this->invoiceDocumentTypeColumn($invoice))
            ->editColumn('issued_at', fn (SinInvoiceIssue $invoice): string => $invoice->issued_at?->format('d/m/Y H:i') ?? '-')
            ->addColumn('customer', fn (SinInvoiceIssue $invoice): string => $this->invoiceCustomerColumn($invoice))
            ->editColumn('total_amount', fn (SinInvoiceIssue $invoice): string => 'Bs '.money_format_decimal($invoice->total_amount))
            ->editColumn('status_label', fn (SinInvoiceIssue $invoice): string => $this->invoiceStatusColumn($invoice))
            ->addColumn('actions', fn (SinInvoiceIssue $invoice): string => $this->invoiceActions($invoice))
            ->rawColumns(['invoice_number', 'document_type', 'customer', 'status_label', 'actions'])
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

    private function invoiceNumberColumn(SinInvoiceIssue $invoice): string
    {
        if ($invoice->invoice_number) {
            $number = '<div class="fw-semibold">Nro. '.e((string) $invoice->invoice_number).'</div>';
        } else {
            $number = '<div class="fw-semibold text-body-secondary">Intento nro. '.e((string) ($invoice->attempted_invoice_number ?? '-')).'</div>'
                .'<div class="text-danger small">No validada</div>';
        }

        return $number.'<div class="text-body-secondary small">Suc. '
            .e((string) $invoice->branch_code)
            .' / PV '
            .e((string) $invoice->point_of_sale_code)
            .'</div>';
    }

    private function invoiceCustomerColumn(SinInvoiceIssue $invoice): string
    {
        return '<div>'.e((string) ($invoice->customer_name ?? '-')).'</div>'
            .'<div class="text-body-secondary small">'
            .e((string) ($invoice->customer_document_number ?? 'Sin documento'))
            .'</div>';
    }

    private function invoiceDocumentTypeColumn(SinInvoiceIssue $invoice): string
    {
        return match ((int) $invoice->document_sector_code) {
            InvoiceDocumentSector::PURCHASE_SALE => '<span class="badge bg-blue-lt">'
                .'<i class="ti ti-receipt-2 me-1" aria-hidden="true"></i>Compra-Venta</span>'
                .'<div class="text-body-secondary small">Sector 1</div>',
            InvoiceDocumentSector::ZERO_RATE => '<span class="badge bg-purple-lt">'
                .'<i class="ti ti-percentage me-1" aria-hidden="true"></i>Tasa Cero</span>'
                .'<div class="text-body-secondary small">Sector 8 · Sin crédito fiscal</div>',
            default => '<span class="badge bg-secondary-lt">Documento sector '
                .e((string) $invoice->document_sector_code).'</span>',
        };
    }

    private function invoiceStatusColumn(SinInvoiceIssue $invoice): string
    {
        $statusTone = match ($invoice->fiscal_status) {
            InvoiceFiscalStatus::Validated,
            InvoiceFiscalStatus::ValidatedAfterContingency,
            InvoiceFiscalStatus::ManualValidated => 'bg-success-lt',
            InvoiceFiscalStatus::Observed,
            InvoiceFiscalStatus::UncertainSend => 'bg-yellow-lt',
            InvoiceFiscalStatus::PendingOnlineSend,
            InvoiceFiscalStatus::PendingPackage,
            InvoiceFiscalStatus::PackageSent => 'bg-blue-lt',
            default => 'bg-danger-lt',
        };

        $status = '<span class="badge '.$statusTone.'">'.e($invoice->fiscal_status->label()).'</span>';

        if ($invoice->failure_category) {
            $status .= '<div class="text-body-secondary small">'.e($invoice->failure_category->label()).'</div>';
        }

        if ($invoice->status_code) {
            $status .= '<div class="text-body-secondary small">Codigo '.e((string) $invoice->status_code).'</div>';
        }

        return $status;
    }

    private function invoiceActions(SinInvoiceIssue $invoice): string
    {
        $xmlAction = $invoice->xml_path
            ? '<a class="btn btn-outline-secondary btn-sm" href="'.e(route('billing.invoices.xml', $invoice)).'" target="_blank" rel="noopener">'
                .'<i class="ti ti-file-code me-1" aria-hidden="true"></i>Ver XML</a>'
            : '';

        if (auth()->user()?->can('invoices.issue')
            && $invoice->fiscal_status === InvoiceFiscalStatus::PendingOnlineSend
            && $invoice->emission_mode === InvoiceEmissionMode::Online) {
            return '<form class="d-inline" method="POST" data-disable-on-submit action="'.e(route('billing.invoices.resend', $invoice)).'">'
                .csrf_field().'<button class="btn btn-outline-primary btn-sm" type="submit" data-submitting-label="Encolando…">'
                .'<i class="ti ti-send me-1" aria-hidden="true"></i><span>Reenviar al SIN</span></button></form>'
                .($xmlAction !== '' ? ' '.$xmlAction : '');
        }

        if (auth()->user()?->can('invoices.issue')
            && in_array($invoice->fiscal_status, [InvoiceFiscalStatus::Observed, InvoiceFiscalStatus::Rejected], true)
            && in_array($invoice->status_code, [904, 902], true)) {
            return '<a class="btn btn-outline-warning btn-sm" href="'.e(route('billing.invoices.payment.correct.form', $invoice)).'">'
                .'<i class="ti ti-edit me-1" aria-hidden="true"></i>Corregir método de pago</a>'
                .($xmlAction !== '' ? ' '.$xmlAction : '');
        }

        $registeredInSiat = ($invoice->status_code === 908 && $invoice->transaccion && $invoice->invoice_number)
            || in_array($invoice->fiscal_status, [InvoiceFiscalStatus::CancelledInSiat, InvoiceFiscalStatus::ReversedInSiat], true);
        $printableOffline = $invoice->emission_mode === InvoiceEmissionMode::OfflineDigital
            && $invoice->invoice_number
            && filled($invoice->cuf)
            && filled($invoice->payload);

        if (! $registeredInSiat && ! $printableOffline) {
            return $xmlAction !== '' ? $xmlAction : '<span class="text-body-secondary small">-</span>';
        }

        $actions = '<a class="btn btn-outline-primary btn-sm" href="'
            .e(route('billing.invoices.print', $invoice))
            .'" target="_blank" rel="noopener">'
            .'<i class="ti ti-printer me-1" aria-hidden="true"></i>Reimprimir'
            .'</a>'
            .($xmlAction !== '' ? ' '.$xmlAction : '');

        if ($registeredInSiat) {
            $actions .= ' <a class="btn btn-outline-info btn-sm" href="'
                .e($this->invoicePdf->verificationUrl($invoice))
                .'" target="_blank" rel="noopener noreferrer">'
                .'<i class="ti ti-shield-check me-1" aria-hidden="true"></i>Verificar factura</a>';
        }

        if (auth()->user()?->can('invoices.cancel')
            && in_array($invoice->fiscal_status, [InvoiceFiscalStatus::Validated, InvoiceFiscalStatus::ValidatedAfterContingency, InvoiceFiscalStatus::ManualValidated], true)) {
            $actions .= ' <a class="btn btn-outline-danger btn-sm" href="'.e(route('billing.invoices.cancel.form', $invoice)).'">'
                .'<i class="ti ti-file-off me-1" aria-hidden="true"></i>Anular</a>';
        }

        if (auth()->user()?->can('invoices.cancel') && $invoice->fiscal_status === InvoiceFiscalStatus::CancelledInSiat) {
            $actions .= ' <a class="btn btn-outline-success btn-sm" href="'.e(route('billing.invoices.reversal.form', $invoice)).'">'
                .'<i class="ti ti-arrow-back-up me-1" aria-hidden="true"></i>Revertir anulación</a>';
        }

        return $actions;
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

    private function activeBadge(bool $active, string $activeLabel = 'Activo', string $inactiveLabel = 'Inactivo'): string
    {
        return '<span class="badge text-bg-'.($active ? 'success' : 'secondary').'">'
            .($active ? $activeLabel : $inactiveLabel).'</span>';
    }

    private function crudActions(?string $editUrl, ?string $deleteUrl, string $label, string $modalTitle): string
    {
        $actions = '<div class="d-inline-flex gap-1">';

        if ($editUrl) {
            $actions .= '<a class="btn btn-outline-primary btn-sm" href="'.e($editUrl).'" data-modal-url="'.e($editUrl).'" data-modal-title="'.e($modalTitle).'">Editar</a>';
        }

        if ($deleteUrl) {
            $actions .= '<form method="POST" action="'.e($deleteUrl).'" data-confirm-delete="¿Eliminar '.e($label).'?">'
                .csrf_field().method_field('DELETE')
                .'<button class="btn btn-outline-danger btn-sm" type="submit" aria-label="Eliminar '.e($label).'"><i class="ti ti-trash" aria-hidden="true"></i></button></form>';
        }

        return $actions.'</div>';
    }

    private function companyActions(Company $company): string
    {
        $showUrl = route('companies.show', $company);
        $actions = '<div class="d-inline-flex gap-1"><a class="btn btn-outline-secondary btn-sm" href="'.e($showUrl).'" data-modal-url="'.e($showUrl).'" data-modal-title="Detalle de empresa">Ver</a>';

        if (auth()->user()?->can('companies.update')) {
            $editUrl = route('companies.edit', $company);
            $actions .= '<a class="btn btn-outline-primary btn-sm" href="'.e($editUrl).'" data-modal-url="'.e($editUrl).'" data-modal-title="Editar empresa">Editar</a>';
        }

        if (auth()->user()?->can('companies.delete')) {
            $actions .= '<form method="POST" action="'.e(route('companies.destroy', $company)).'" data-confirm-delete="¿Eliminar empresa?">'.csrf_field().method_field('DELETE').'<button class="btn btn-outline-danger btn-sm" type="submit">Eliminar</button></form>';
        }

        return $actions.'</div>';
    }

    private function permissionActions(Permission $permission): string
    {
        $show = route('permissions.show', $permission);
        $html = '<div class="d-inline-flex gap-1"><a class="btn btn-outline-secondary btn-sm" href="'.e($show).'" data-modal-url="'.e($show).'" data-modal-title="Detalle de permiso">Ver</a>';
        if (auth()->user()?->can('permissions.edit')) {
            $edit = route('permissions.edit', $permission);
            $html .= '<a class="btn btn-outline-primary btn-sm" href="'.e($edit).'" data-modal-url="'.e($edit).'" data-modal-title="Editar permiso">Editar</a>';
        }
        if (auth()->user()?->can('permissions.delete')) {
            $html .= $this->deleteForm(route('permissions.destroy', $permission), '¿Eliminar permiso?');
        }

        return $html.'</div>';
    }

    private function roleActions(Role $role): string
    {
        $show = route('roles.show', $role);
        $html = '<div class="d-inline-flex gap-1"><a class="btn btn-outline-secondary btn-sm" href="'.e($show).'" data-modal-url="'.e($show).'" data-modal-title="Detalle de rol">Ver</a>';
        if (auth()->user()?->can('roles.edit')) {
            $edit = route('roles.edit', $role);
            $html .= '<a class="btn btn-outline-primary btn-sm" href="'.e($edit).'" data-modal-url="'.e($edit).'" data-modal-title="Editar rol" data-modal-size="xl">Editar</a>';
        }
        if (auth()->user()?->can('roles.assign-permissions')) {
            $url = route('roles.permissions.form', $role);
            $html .= '<a class="btn btn-outline-info btn-sm" href="'.e($url).'" data-modal-url="'.e($url).'" data-modal-title="Configurar permisos" data-modal-size="xl">Configurar acceso</a>';
        }
        if (auth()->user()?->can('roles.delete')) {
            $html .= $this->deleteForm(route('roles.destroy', $role), '¿Eliminar rol?');
        }

        return $html.'</div>';
    }

    private function userActions(User $user): string
    {
        $show = route('users.show', $user);
        $html = '<div class="d-inline-flex gap-1"><a class="btn btn-outline-secondary btn-sm" href="'.e($show).'" data-modal-url="'.e($show).'" data-modal-title="Detalle de usuario">Ver</a>';
        if (! $user->trashed()) {
            if (auth()->user()?->can('users.edit')) {
                $edit = route('users.edit', $user);
                $html .= '<a class="btn btn-outline-primary btn-sm" href="'.e($edit).'" data-modal-url="'.e($edit).'" data-modal-title="Editar usuario">Editar</a>';
            }
            if (auth()->user()?->can('users.assign-roles')) {
                $roles = route('users.roles.form', $user);
                $html .= '<a class="btn btn-outline-info btn-sm" href="'.e($roles).'" data-modal-url="'.e($roles).'" data-modal-title="Asignar roles">Roles</a>';
            }
            if (auth()->user()?->can('users.edit')) {
                $html .= '<form method="POST" action="'.e(route('users.toggle-status', $user)).'">'.csrf_field().method_field('PATCH').'<button class="btn btn-outline-secondary btn-sm" type="submit">'.($user->is_active ? 'Desactivar' : 'Activar').'</button></form>';
            }
            if (auth()->user()?->can('users.delete')) {
                $html .= $this->deleteForm(route('users.destroy', $user), '¿Eliminar usuario?');
            }
        } elseif (auth()->user()?->can('users.restore')) {
            $html .= '<form method="POST" action="'.e(route('users.restore', $user->id)).'">'.csrf_field().method_field('PATCH').'<button class="btn btn-outline-success btn-sm" type="submit">Restaurar</button></form>';
        }

        return $html.'</div>';
    }

    private function deleteForm(string $url, string $confirmation): string
    {
        return '<form method="POST" action="'.e($url).'" data-confirm-delete="'.e($confirmation).'">'.csrf_field().method_field('DELETE').'<button class="btn btn-outline-danger btn-sm" type="submit">Eliminar</button></form>';
    }

    private function academicModuleActions(AcademicModule $module): string
    {
        if (! auth()->user()?->can('academic-modules.manage')) {
            return '';
        }
        $teacher = route('academic.modules.teacher.edit', $module);
        $edit = route('academic.modules.edit', $module);

        return '<div class="d-inline-flex gap-1"><a class="btn btn-outline-success btn-sm" href="'.e($teacher).'" data-modal-url="'.e($teacher).'" data-modal-title="Asignar docente" data-modal-size="lg"><i class="ti ti-user-check me-1"></i>Docente</a><a class="btn btn-outline-primary btn-sm" href="'.e($edit).'" data-modal-url="'.e($edit).'" data-modal-title="Editar módulo" data-modal-size="lg">Editar</a>'.$this->deleteForm(route('academic.modules.destroy', $module), '¿Eliminar el módulo '.$module->name.'?').'</div>';
    }

    private function enrollmentProgress(RectorateApplication $application): string
    {
        if ($application->contract?->status === 'cancelled') {
            return '<span class="badge text-bg-secondary"><i class="ti ti-ban me-1"></i>Inhabilitada</span>';
        }
        if ($application->status === 'completed') {
            return '<span class="badge text-bg-success">Completada</span>';
        }
        $step = max(1, min(4, (int) $application->current_step));
        $label = match ($step) {
            1 => 'Titular', 2 => 'Programa', 3 => 'Estudiante', default => 'Confirmación'
        };

        return '<span class="badge text-bg-azure">Paso '.$step.' de 4 · '.$label.'</span>';
    }

    private function enrollmentActions(RectorateApplication $application): string
    {
        $step = max(1, min(4, (int) $application->current_step));
        $continueUrl = match ($step) {
            1, 2 => route('rectorate.applications.plan.edit', $application),
            3 => route('rectorate.applications.student.edit', $application),
            default => route('rectorate.applications.confirmation.show', $application),
        };
        $label = match (true) {
            $application->status === 'completed' => 'Ver resumen',
            $step === 2 => 'Continuar programa',
            $step === 3 => 'Continuar estudiante',
            $step === 4 => 'Confirmar',
            default => 'Continuar',
        };
        $html = '<div class="d-inline-flex gap-1"><a class="btn btn-outline-primary btn-sm" href="'.e($continueUrl).'"><i class="ti ti-player-play me-1"></i>'.$label.'</a>';
        if ($application->contract) {
            $html .= '<a class="btn btn-outline-dark btn-sm" href="'.e(route('rectorate.contracts.print', $application->contract)).'" target="_blank" rel="noopener"><i class="ti ti-printer me-1"></i>Contrato</a>';
            if ($application->contract->status !== 'cancelled') {
                $html .= '<a class="btn btn-outline-success btn-sm" href="'.e(route('rectorate.contracts.account.show', $application->contract)).'"><i class="ti ti-cash me-1"></i>Estado de cuenta</a>';
            }
            if (auth()->user()?->can('rectorate.delete') && $application->status === 'completed' && $application->contract->status !== 'cancelled' && $application->contract->payments_count === 0 && (float) $application->contract->charges_sum_paid_amount === 0.0) {
                $html .= '<form method="POST" action="'.e(route('rectorate.contracts.disable', $application->contract)).'" data-confirm-action data-confirm-title="¿Inhabilitar el contrato de la matrícula '.e($application->account_number).'?" data-confirm-text="El contrato quedará en el historial; se eliminarán el estudiante y los cargos pendientes para que no afecten los reportes económicos." data-confirm-button="Sí, inhabilitar contrato">'.csrf_field().method_field('PATCH').'<button class="btn btn-outline-danger btn-sm" type="submit"><i class="ti ti-ban me-1"></i>Inhabilitar contrato</button></form>';
            }
        }
        if (auth()->user()?->can('rectorate.delete') && $application->status !== 'completed') {
            $html .= $this->deleteForm(route('rectorate.applications.destroy', $application), '¿Eliminar la inscripción #'.$application->id.'?');
        }

        return $html.'</div>';
    }
}
