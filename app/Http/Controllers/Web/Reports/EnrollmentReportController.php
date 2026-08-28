<?php

namespace App\Http\Controllers\Web\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\EnrollmentReportRequest;
use App\Models\Campus;
use App\Models\CommercialOrigin;
use App\Models\Personnel;
use App\Models\Plan;
use App\Models\Program;
use App\Services\Reports\EnrollmentReportPdfService;
use App\Services\Reports\EnrollmentReportService;
use App\Support\CompanyContext;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class EnrollmentReportController extends Controller
{
    public function __construct(
        private readonly EnrollmentReportService $report,
        private readonly EnrollmentReportPdfService $pdf,
    ) {}

    public function index(EnrollmentReportRequest $request): View
    {
        $filters = $request->validated();

        return view('reports.enrollments.index', [
            ...$this->data($filters),
            'applications' => $this->report->query($filters)->paginate(25)->withQueryString(),
        ]);
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
                ->whereHas('position', fn ($query) => $query->whereRaw('LOWER(name) = ?', ['ejecutivo de ventas']))
                ->orderBy('first_name')->orderBy('paternal_surname')->get(),
            'commercialOrigins' => CommercialOrigin::query()->orderBy('name')->get(),
        ];
    }
}
