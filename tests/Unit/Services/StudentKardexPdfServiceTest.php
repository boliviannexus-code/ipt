<?php

namespace Tests\Unit\Services;

use App\Models\Campus;
use App\Models\Company;
use App\Models\RectorateApplication;
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

    public function test_it_includes_the_holder_section_in_the_kardex_template(): void
    {
        $student = new Student(['first_name' => 'Ana', 'paternal_surname' => 'Flores']);
        $student->setRelation('contracts', new Collection);
        $holder = new RectorateApplication([
            'identity_document' => '4567890',
            'first_name' => 'María',
            'paternal_surname' => 'Flores',
            'phone' => '70000001',
            'email' => 'maria@example.com',
            'student_relationship' => 'Madre',
        ]);

        $html = view('students.kardex-pdf', [
            'student' => $student,
            'holder' => $holder,
            'academicRows' => new Collection,
        ])->render();

        $this->assertStringContainsString('Datos del titular', $html);
        $this->assertStringContainsString('María Flores', $html);
        $this->assertStringContainsString('4567890', $html);
        $this->assertStringContainsString('Madre', $html);
    }
}
