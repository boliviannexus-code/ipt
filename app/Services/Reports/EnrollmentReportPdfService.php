<?php

namespace App\Services\Reports;

use TCPDF;

class EnrollmentReportPdfService
{
    public function render(array $data): string
    {
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(config('app.name'));
        $pdf->SetTitle('Reporte de matrículas');
        $pdf->setPrintHeader(false);
        $pdf->SetMargins(8, 9, 8);
        $pdf->SetAutoPageBreak(true, 14);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 8);
        $pdf->writeHTML(view('reports.enrollments.pdf', $data)->render(), true, false, true, false, '');

        return $pdf->Output('reporte-matriculas.pdf', 'S');
    }
}
