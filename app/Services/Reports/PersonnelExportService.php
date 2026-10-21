<?php

namespace App\Services\Reports;

use App\Models\Personnel;
use Illuminate\Support\Enumerable;
use TCPDF;

class PersonnelExportService
{
    public const HEADINGS = ['Nombre', 'CI', 'Área', 'Cargo', 'Sede', 'Teléfono', 'Usuario', 'Ventas'];

    public function rows(Enumerable $personnel): array
    {
        return $personnel->map(fn (Personnel $person): array => [
            $person->full_name,
            $person->identity_document,
            $person->position?->area?->name ?? '—',
            $person->position?->name ?? '—',
            $person->campus ? ($person->campus->short ?: '—') : 'Sin asignar',
            $person->phone ?: '—',
            $person->user?->email ?? 'Sin usuario',
            $person->is_sales_enabled ? 'Habilitado' : 'Deshabilitado',
        ])->all();
    }

    public function csv(Enumerable $personnel): string
    {
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, self::HEADINGS, ';', '"', '');
        foreach ($this->rows($personnel) as $row) {
            // Prevent spreadsheet applications from interpreting user data as formulas.
            $row = array_map(static fn ($value): string => preg_match('/^[\s]*[=+@-]/u', (string) $value)
                ? "'".$value : (string) $value, $row);
            fputcsv($stream, $row, ';', '"', '');
        }
        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        return $contents;
    }

    public function pdf(Enumerable $personnel): string
    {
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(config('app.name'));
        $pdf->SetTitle('Listado de personal');
        $pdf->setPrintHeader(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->writeHTML(view('personnel.export-pdf', [
            'headings' => self::HEADINGS,
            'rows' => $this->rows($personnel),
        ])->render(), true, false, true, false, '');

        return $pdf->Output('listado-personal.pdf', 'S');
    }
}
