<?php

namespace App\Services\Academic;

use TCPDF;

class StudentKardexPdfService
{
    public function render(array $kardex): string
    {
        $student = $kardex['student'];
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(config('app.name'));
        $pdf->SetAuthor($student->company?->name ?? config('app.name'));
        $pdf->SetTitle('Kardex académico - '.$student->account_number);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->setFooterFont(['helvetica', '', 8]);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 9);
        $pdf->writeHTML(view('students.kardex-pdf', $kardex)->render(), true, false, true, false, '');

        return $pdf->Output($this->filename($student->account_number), 'S');
    }

    public function filename(?string $accountNumber): string
    {
        $safeAccount = preg_replace('/[^A-Za-z0-9_-]/', '', $accountNumber ?? '') ?: 'estudiante';

        return "kardex-{$safeAccount}.pdf";
    }
}
