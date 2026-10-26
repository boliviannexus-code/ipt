<?php

declare(strict_types=1);

namespace App\Services\Academic;

use TCPDF;

final class TeacherTrackingPdfService
{
    public function render(array $data): string
    {
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(config('app.name'));
        $pdf->SetTitle('Centralizador de notas · '.$data['module']->name);
        $pdf->setPrintHeader(false);
        $pdf->SetMargins(8, 9, 8);
        $pdf->SetAutoPageBreak(true, 14);
        $pdf->AddPage();
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->writeHTML(view('teacher.tracking.pdf', $data)->render(), true, false, true, false, '');

        return $pdf->Output('centralizador-notas-'.$data['module']->id.'.pdf', 'S');
    }
}
