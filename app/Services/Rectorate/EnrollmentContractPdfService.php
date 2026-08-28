<?php

namespace App\Services\Rectorate;

use App\Models\EnrollmentContract;
use TCPDF;

class EnrollmentContractPdfService
{
    public function render(EnrollmentContract $contract): string
    {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(config('app.name'));
        $pdf->SetAuthor($contract->application->company?->name ?? config('app.name'));
        $pdf->SetTitle('Contrato N.º '.$contract->contract_number);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(8, 12, 8);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->AddPage();
        $pdf->SetFont('times', '', 9);
        $pdf->writeHTML(view('rectorate.contract-pdf', compact('contract'))->render(), true, false, true, false, '');

        return $pdf->Output($this->filename($contract->contract_number), 'S');
    }

    public function filename(int $contractNumber): string
    {
        return 'contrato-'.str_pad((string) $contractNumber, 5, '0', STR_PAD_LEFT).'.pdf';
    }
}
