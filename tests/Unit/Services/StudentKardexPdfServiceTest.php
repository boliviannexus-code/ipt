<?php

namespace Tests\Unit\Services;

use App\Models\Campus;
use App\Models\Company;
use App\Models\Student;
use App\Services\Academic\StudentKardexPdfService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class StudentKardexPdfServiceTest extends TestCase
{
    public function test_it_renders_a_tcpdf_kardex_with_the_student_account_filename(): void
    {
        $company = new Company(['name' => 'Instituto de Prueba']);
        $campus = new Campus(['name' => 'Sede Central', 'code' => '1']);
        $student = new Student([
            'account_number' => '10001',
            'identity_document' => '7456123',
            'first_name' => 'Ana',
            'paternal_surname' => 'Flores',
            'birth_date' => '2010-05-10',
            'email' => 'ana@example.com',
            'phone' => '71234567',
        ]);
        $student->setRelation('company', $company);
        $student->setRelation('campus', $campus);
        $student->setRelation('contracts', new Collection);

        $service = app(StudentKardexPdfService::class);
        $contents = $service->render(['student' => $student, 'academicRows' => new Collection]);

        $this->assertStringStartsWith('%PDF-', $contents);
        $this->assertSame('kardex-10001.pdf', $service->filename($student->account_number));
    }
}
