<?php

namespace App\Services\Reports;

use App\Enums\AccountPaymentMethod;
use Illuminate\Support\Enumerable;

class EnrollmentReportCsvService
{
    public function render(Enumerable $applications): string
    {
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, [
            'Fecha', 'Matrícula', 'Sede', 'Estudiante', 'CI', 'Programa', 'Plan',
            'Ejecutivo', 'Origen', 'Método de pago', 'Referencias', 'Cargos',
            'Recaudado', 'Saldo', 'Estado',
        ], ';');

        $labels = AccountPaymentMethod::labels();
        foreach ($applications as $application) {
            $charged = (float) ($application->contract?->charges->sum('amount') ?? 0);
            $collected = (float) ($application->contract?->charges->sum('paid_amount') ?? 0);
            $payments = $application->contract?->payments ?? collect();
            $methods = $payments->pluck('payment_method_code')->unique()
                ->map(fn ($code): string => $labels->get((int) $code, 'Método no disponible'))->join(', ');

            fputcsv($stream, [
                $application->created_at->format('d/m/Y'),
                $application->account_number ?: 'Pendiente',
                $application->campus?->name ?? 'Sin sede',
                trim("{$application->student_first_name} {$application->student_paternal_surname} {$application->student_maternal_surname}")
                    ?: trim("{$application->first_name} {$application->paternal_surname}"),
                $application->student_identity_document ?: $application->identity_document,
                $application->program?->title ?? 'Pendiente',
                $application->plan?->name ?? '',
                $application->salesExecutive?->full_name ?? 'Pendiente',
                $application->commercialOrigin?->name ?? 'Pendiente',
                $methods ?: 'Sin pagos',
                $payments->pluck('reference')->filter()->unique()->join(', '),
                number_format($charged, 2, '.', ''),
                number_format($collected, 2, '.', ''),
                number_format($charged - $collected, 2, '.', ''),
                $application->status === 'completed' ? 'Completada' : 'En proceso',
            ], ';');
        }

        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        return $contents;
    }
}
