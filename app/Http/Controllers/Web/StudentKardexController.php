<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AcademicModule;
use App\Models\Student;
use App\Services\Academic\StudentKardexPdfService;
use App\Services\Academic\StudentKardexService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class StudentKardexController extends Controller
{
    public function __construct(
        private readonly StudentKardexService $kardex,
        private readonly StudentKardexPdfService $pdf,
    ) {}

    public function show(Student $student): View
    {
        return view('students.kardex', $this->kardex->build($student));
    }

    public function details(Student $student, AcademicModule $module): View
    {
        return view('students.kardex-details', $this->kardex->dailyDetails($student, $module));
    }

    public function print(Student $student): Response
    {
        $contents = $this->pdf->render($this->kardex->build($student));

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$this->pdf->filename($student->account_number).'"',
        ]);
    }
}
