<?php

namespace App\Services\Imports;

use App\Models\Campus;
use App\Models\CommercialOrigin;
use App\Models\Customer;
use App\Models\Personnel;
use App\Models\Plan;
use App\Models\Program;
use App\Models\RectorateApplication;
use App\Models\Student;
use App\Services\Rectorate\EnrollmentStepService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class EnrollmentCsvImport
{
    public function read(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException('No se pudo leer el CSV.');
        }
        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, preg_replace('/^\xEF\xBB\xBF/', '', $content));
        rewind($stream);
        $header = fgetcsv($stream, escape: '');
        $rows = [];
        while (($values = fgetcsv($stream, escape: '')) !== false) {
            if ($values === [null]) {
                continue;
            }
            if (count($values) !== count($header)) {
                throw new RuntimeException('Cantidad incorrecta de columnas.');
            }
            $rows[] = array_map(fn ($value) => trim($value), array_combine($header, $values));
        }
        fclose($stream);

        return $rows;
    }

    public function run(array $rows, Campus $campus, string $batch, string $gender, array $surnameOverrides = [], bool $commit = false): array
    {
        if (! in_array($gender, ['Otro', 'Femenino', 'Masculino'], true) || $batch === '' || $rows === []) {
            throw new RuntimeException('Importación sin datos, lote o género válido.');
        }
        $company = $campus->company_id;
        $resolve = function (string $class, string $field, string $text) use ($company) {
            $matches = $class::withoutGlobalScope('company')->where('company_id', $company)->get()
                ->filter(fn ($model) => $this->key($model->$field) === $this->key($text));
            if ($matches->count() !== 1) {
                throw new RuntimeException("Catálogo inexistente o ambiguo: {$text}");
            }

            return $matches->first();
        };
        DB::beginTransaction();
        try {
            // Serialize imports in this company, including allocation of customer codes.
            DB::table('companies')->where('id', $company)->lockForUpdate()->first();
            $seen = [];
            $holders = [];
            $report = [];
            foreach ($rows as $row) {
                foreach (['numero', 'fecha', 'nombre_titular', 'paterno_titular', 'materno_titular', 'ci_titular', 'programa', 'plan', 'origen_comercial', 'nombre_estudiante', 'paterno_estudiante', 'materno_estudiante', 'ci_estudiante', 'contacto'] as $field) {
                    if (! isset($row[$field]) || $row[$field] === '') {
                        throw new RuntimeException("Campo faltante: {$field}");
                    }
                }
                $number = $row['numero'];
                if (isset($seen[$number])) {
                    throw new RuntimeException("Número repetido: {$number}");
                }
                $seen[$number] = true;
                $row['paterno_titular'] = $surnameOverrides[$row['ci_titular']] ?? $row['paterno_titular'];
                $fingerprint = hash('sha256', json_encode([$row, $gender, $campus->id], JSON_UNESCAPED_UNICODE));
                $import = DB::table('enrollment_import_rows')->where(['company_id' => $company, 'batch' => $batch, 'source_number' => $number])->first();
                if ($import) {
                    if ($import->fingerprint !== $fingerprint) {
                        throw new RuntimeException("Fila {$number} ya importada con datos diferentes.");
                    }
                    $application = RectorateApplication::withoutGlobalScope('company')->findOrFail($import->rectorate_application_id);
                    $report[] = $this->report($row, $application, 'existente');

                    continue;
                }
                $date = Carbon::createFromFormat('!m/d/Y', $row['fecha']);
                if ($date->format('n/j/Y') !== $row['fecha']) {
                    throw new RuntimeException("Fecha inválida en fila {$number}");
                }
                $program = $resolve(Program::class, 'title', $row['programa']);
                $plan = $resolve(Plan::class, 'name', $row['plan']);
                $origin = $resolve(CommercialOrigin::class, 'name', $row['origen_comercial']);
                $executive = $resolve(Personnel::class, 'full_name', $row['ejecutivo_de_ventas'] ?? $row['jecutivo_de_ventas'] ?? '');
                if (! $executive->is_active || ! $executive->is_sales_enabled || ! $plan->programs()->whereKey($program->id)->exists()) {
                    throw new RuntimeException("Plan o ejecutivo inválido en fila {$number}");
                }
                foreach (['ci_titular', 'ci_estudiante'] as $field) {
                    if (! preg_match('/^\d{5,10}$/', $row[$field])) {
                        throw new RuntimeException("CI inválido en fila {$number}");
                    }
                }
                $phone = preg_replace('/\b(\d+)\.0+\b/', '$1', $row['contacto']);
                if (mb_strlen($phone) > 30) {
                    throw new RuntimeException("Contacto demasiado largo en fila {$number}");
                }
                $holder = array_map(fn ($field) => $this->name($row[$field]), ['nombre_titular', 'paterno_titular', 'materno_titular']);
                if (isset($holders[$row['ci_titular']]) && $holders[$row['ci_titular']] !== $holder) {
                    throw new RuntimeException("Titular con nombres contradictorios en fila {$number}");
                }
                $holders[$row['ci_titular']] = $holder;
                $same = $row['ci_titular'] === $row['ci_estudiante'];
                if ($same && $holder !== array_map(fn ($field) => $this->name($row[$field]), ['nombre_estudiante', 'paterno_estudiante', 'materno_estudiante'])) {
                    throw new RuntimeException("CI igual con nombres diferentes en fila {$number}");
                }
                if (Student::withoutGlobalScope('company')->where('company_id', $company)->where('identity_document', $row['ci_estudiante'])->exists()) {
                    throw new RuntimeException("Estudiante existente fuera de este lote en fila {$number}; revisar antes de sobrescribir.");
                }
                $application = Carbon::withTestNow($date, function () use ($row, $company, $campus, $holder, $phone, $same, $gender, $program, $plan, $origin, $executive) {
                    $customer = Customer::withTrashed()->withoutGlobalScope('company')->where('company_id', $company)
                        ->where('identity_document_type_code', 1)->where('document_number', $row['ci_titular'])->whereNull('document_complement')->first();
                    if ($customer?->trashed()) {
                        throw new RuntimeException('Titular eliminado: requiere revisión.');
                    }
                    if (! $customer) {
                        $customer = Customer::create([
                            'company_id' => $company, 'identity_document_type_code' => 1,
                            'document_number' => $row['ci_titular'], 'customer_code' => 'CLI-'.$row['ci_titular'],
                            'name' => $holder[1], 'email' => 'migracion@gmail.com', 'phone' => $phone, 'is_active' => true,
                        ]);
                    }
                    $application = RectorateApplication::create([
                        'company_id' => $company, 'campus_id' => $campus->id, 'customer_id' => $customer->id,
                        'identity_document' => $row['ci_titular'], 'first_name' => $holder[0], 'paternal_surname' => $holder[1], 'maternal_surname' => $holder[2],
                        'birth_date' => '1985-12-11', 'email' => 'migracion@gmail.com', 'phone' => $phone, 'status' => 'draft', 'current_step' => 2,
                    ]);
                    $service = app(EnrollmentStepService::class);
                    $application = $service->assignPlan($application, $program->id, $plan->id, $origin->id, $executive->id);
                    $application = $service->saveStudent($application, [
                        'student_identity_document' => $row['ci_estudiante'], 'student_first_name' => $row['nombre_estudiante'],
                        'student_paternal_surname' => $row['paterno_estudiante'], 'student_maternal_surname' => $row['materno_estudiante'],
                        'student_birth_date' => '1985-12-11', 'student_email' => 'migracion@gmail.com', 'student_phone' => $phone,
                        'student_gender' => $gender, 'student_relationship' => $same ? 'Titular' : 'Otro', 'primary_contact_type' => 'Titular',
                    ]);
                    $service->confirm($application);

                    return $application->refresh();
                });
                DB::table('enrollment_import_rows')->insert([
                    'company_id' => $company, 'batch' => $batch, 'source_number' => $number,
                    'fingerprint' => $fingerprint, 'rectorate_application_id' => $application->id, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $report[] = $this->report($row, $application, 'creado');
            }
            $commit ? DB::commit() : DB::rollBack();

            return $report;
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    private function key(string $value): string
    {
        return Str::upper(Str::ascii(Str::squish($value)));
    }

    private function name(string $value): string
    {
        return Str::title(Str::lower(Str::squish($value)));
    }

    private function report(array $row, RectorateApplication $application, string $result): array
    {
        $contract = $application->contract;

        return [
            'fila' => $row['numero'], 'fecha_inscripcion' => $application->created_at->toDateString(),
            'titular_id' => $application->customer_id, 'ci_titular' => $application->identity_document,
            'titular' => trim($application->first_name.' '.$application->paternal_surname.' '.$application->maternal_surname),
            'estudiante_id' => $application->student_id, 'ci_estudiante' => $application->student_identity_document,
            'estudiante' => trim($application->student_first_name.' '.$application->student_paternal_surname.' '.$application->student_maternal_surname),
            'parentesco' => $application->student_relationship, 'programa' => $row['programa'], 'plan' => $row['plan'],
            'matricula' => $application->account_number, 'contrato_id' => $contract->id, 'estado' => $contract->status,
            'cargo' => $contract->charges()->sum('amount'), 'resultado' => $result,
        ];
    }
}
