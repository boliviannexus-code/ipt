<?php

namespace App\Http\Controllers\Web\Reports;

use App\Enums\AccountPaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\EnrollmentReportRequest;
use App\Models\Campus;
use App\Models\CommercialOrigin;
use App\Models\Personnel;
use App\Models\Plan;
use App\Models\Program;
use App\Services\Reports\EnrollmentReportCsvService;
use App\Services\Reports\EnrollmentReportPdfService;
use App\Services\Reports\EnrollmentReportService;
use App\Support\CompanyContext;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use App\Models\RectorateApplication;
use Yajra\DataTables\Facades\DataTables;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnrollmentReportController extends Controller
{
    public function __construct(
        private readonly EnrollmentReportService $report,
        private readonly EnrollmentReportPdfService $pdf,
        private readonly EnrollmentReportCsvService $csv,
    ) {}

    public function index(EnrollmentReportRequest $request): View
    {
        $filters = $request->validated();

        return view('reports.enrollments.index', [
            ...$this->data($filters),
        ]);
    }

    public function dataTable(EnrollmentReportRequest $request): JsonResponse
    {
        $paymentMethods = AccountPaymentMethod::options();

        return DataTables::eloquent($this->report->query($request->validated()))
            ->editColumn('created_at', fn (RectorateApplication $item): string => $item->created_at->format('d/m/Y'))
            ->addColumn('enrollment', fn (RectorateApplication $item): string => '<span class="fw-semibold d-block">'.e($item->account_number ?: 'Pendiente').'</span><small class="text-secondary">'.e($item->campus?->name ?? 'Sin sede').'</small>')
            ->addColumn('student_name', fn (RectorateApplication $item): string => '<span class="d-block">'.e(trim("{$item->student_first_name} {$item->student_paternal_surname} {$item->student_maternal_surname}") ?: trim("{$item->first_name} {$item->paternal_surname}")).'</span><small class="text-secondary">CI '.e($item->student_identity_document ?: $item->identity_document).'</small>')
            ->addColumn('program_plan', fn (RectorateApplication $item): string => '<span class="d-block">'.e($item->program?->title ?? 'Pendiente').'</span><small class="text-secondary">'.e($item->plan?->name ?? '').'</small>')
            ->addColumn('executive', fn (RectorateApplication $item): string => e($item->salesExecutive?->full_name ?? 'Pendiente'))
            ->addColumn('origin', fn (RectorateApplication $item): string => e($item->commercialOrigin?->name ?? 'Pendiente'))
            ->addColumn('payment', fn (RectorateApplication $item): string => e($item->contract?->payments->pluck('payment_method_code')->unique()->map(fn ($code) => $paymentMethods->firstWhere('code', (int) $code)['label'] ?? 'No disponible')->join(', ') ?: 'Sin pagos'))
            ->addColumn('charged', fn (RectorateApplication $item): string => 'Bs '.number_format((float) ($item->contract?->charges->sum('amount') ?? 0), 2, ',', '.'))
            ->addColumn('collected', fn (RectorateApplication $item): string => 'Bs '.number_format((float) ($item->contract?->charges->sum('paid_amount') ?? 0), 2, ',', '.'))
            ->addColumn('balance', function (RectorateApplication $item): string {
                $charged = (float) ($item->contract?->charges->sum('amount') ?? 0);
                $collected = (float) ($item->contract?->charges->sum('paid_amount') ?? 0);
                return 'Bs '.number_format($charged - $collected, 2, ',', '.');
            })
            ->editColumn('status', fn (RectorateApplication $item): string => '<span class="badge '.($item->status === 'completed' ? 'text-bg-success' : 'text-bg-azure').'">'.($item->status === 'completed' ? 'Completada' : 'En proceso').'</span>')
            ->rawColumns(['enrollment', 'student_name', 'program_plan', 'status'])
            ->toJson();
    }

    public function print(EnrollmentReportRequest $request): Response
    {
        $filters = $request->validated();
        $data = $this->data($filters);
        $data['applications'] = $this->report->query($filters)->get();

        return response($this->pdf->render($data), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="reporte-matriculas.pdf"',
        ]);
    }

    public function export(EnrollmentReportRequest $request): StreamedResponse
    {
        $filters = $request->validated();
        $contents = $this->csv->render($this->report->query($filters)->get());

        return response()->streamDownload(
            static fn () => print $contents,
            'reporte-matriculas-'.now()->format('Ymd-His').'.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    private function data(array $filters): array
    {
        return [
            'filters' => $filters,
            'company' => CompanyContext::activeCompany(request()->user()),
            'summary' => $this->report->summary($filters),
            'campuses' => Campus::query()->orderBy('name')->get(),
            'programs' => Program::query()->orderBy('title')->get(),
            'plans' => Plan::query()->orderBy('name')->get(),
            'salesExecutives' => Personnel::query()->where('is_active', true)
                ->where('is_sales_enabled', true)
                ->orderBy('first_name')->orderBy('paternal_surname')->get(),
            'commercialOrigins' => CommercialOrigin::query()->orderBy('name')->get(),
            'paymentMethods' => AccountPaymentMethod::options(),
        ];
    }
}
