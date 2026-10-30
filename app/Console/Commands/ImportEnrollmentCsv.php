<?php

namespace App\Console\Commands;

use App\Models\Campus;
use App\Services\Imports\EnrollmentCsvImport;
use Illuminate\Console\Command;
use RuntimeException;

class ImportEnrollmentCsv extends Command
{
    protected $signature = 'enrollments:import-csv {file} {--campus=} {--batch=} {--gender=} {--holder-surname=*} {--commit} {--report=}';

    protected $description = 'Importa inscripciones históricas; simula salvo que se indique --commit.';

    public function handle(EnrollmentCsvImport $import): int
    {
        try {
            $campus = Campus::withoutGlobalScope('company')->findOrFail($this->option('campus'));
            $overrides = [];
            foreach ($this->option('holder-surname') as $override) {
                $parts = explode('=', $override, 2);
                if (count($parts) !== 2) {
                    throw new RuntimeException('Usa --holder-surname=CI=APELLIDO');
                }
                $overrides[$parts[0]] = $parts[1];
            }
            $reportPath = $this->option('report');
            if (! $reportPath || ! is_dir(dirname($reportPath)) || ! is_writable(dirname($reportPath))) {
                throw new RuntimeException('Indica --report con un directorio existente y escribible.');
            }
            $report = $import->run($import->read($this->argument('file')), $campus, (string) $this->option('batch'), (string) $this->option('gender'), $overrides, (bool) $this->option('commit'));
            $stream = fopen($reportPath, 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, array_keys($report[0]), escape: '');
            foreach ($report as $row) {
                fputcsv($stream, $row, escape: '');
            }
            fclose($stream);
            $this->info(($this->option('commit') ? 'IMPORTADO' : 'SIMULACIÓN SIN GUARDAR').': '.count($report).' filas. Reporte: '.$reportPath);

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
