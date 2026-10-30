<?php

namespace Tests\Unit\Services;

use App\Services\Reports\EnrollmentReportCsvService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EnrollmentReportCsvServiceTest extends TestCase
{
    public function test_it_exports_payment_methods_and_references_for_excel(): void
    {
        $application = (object) [
            'created_at' => Carbon::parse('2026-08-29'),
            'account_number' => 'MAT-001',
            'campus' => (object) ['name' => 'Central'],
            'student_first_name' => 'Ana',
            'student_paternal_surname' => 'Pérez',
            'student_maternal_surname' => '',
            'first_name' => '',
            'paternal_surname' => '',
            'student_identity_document' => '1234567',
            'identity_document' => '',
            'program' => (object) ['title' => 'Inglés'],
            'plan' => (object) ['name' => 'Mensual'],
            'salesExecutive' => (object) ['full_name' => 'Ejecutiva Uno'],
            'commercialOrigin' => (object) ['name' => 'Redes'],
            'contract' => (object) [
                'charges' => collect([(object) ['amount' => 250, 'paid_amount' => 100]]),
                'payments' => collect([(object) ['payment_method_code' => 2, 'reference' => 'QR-123']]),
            ],
            'status' => 'completed',
        ];

        $csv = app(EnrollmentReportCsvService::class)->render(collect([$application]));

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Método de pago', $csv);
        $this->assertStringContainsString('QR', $csv);
        $this->assertStringContainsString('QR-123', $csv);
        $this->assertStringContainsString('150.00', $csv);
    }
}
