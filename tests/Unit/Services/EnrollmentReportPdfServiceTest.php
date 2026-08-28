<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Services\Reports\EnrollmentReportPdfService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class EnrollmentReportPdfServiceTest extends TestCase
{
    public function test_it_renders_the_filtered_enrollment_report_with_tcpdf(): void
    {
        $empty = new Collection;
        $contents = app(EnrollmentReportPdfService::class)->render([
            'company' => new Company(['name' => 'Instituto de Prueba']),
            'filters' => ['date_from' => '2026-08-01', 'date_to' => '2026-08-31'],
            'summary' => ['total' => 0, 'completed' => 0, 'draft' => 0, 'campuses' => 0, 'charged' => 0, 'collected' => 0, 'balance' => 0],
            'applications' => $empty,
            'campuses' => $empty,
            'programs' => $empty,
            'plans' => $empty,
            'salesExecutives' => $empty,
            'commercialOrigins' => $empty,
        ]);

        $this->assertStringStartsWith('%PDF-', $contents);
    }
}
