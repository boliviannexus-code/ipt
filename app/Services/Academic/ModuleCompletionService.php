<?php

namespace App\Services\Academic;

use App\Models\AcademicModule;
use App\Models\AcademicModuleStudentResult;
use App\Models\EnrollmentContract;
use App\Models\User;
use App\Services\Enrollment\ContractService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ModuleCompletionService
{
    public function __construct(private readonly ContractService $contracts) {}

    public function complete(User $user, AcademicModule $module, array $results, array $concepts): void
    {
        DB::transaction(function () use ($user, $module, $results, $concepts): void {
            foreach ($results as $studentId => $status) {
                $concept = trim((string) $concepts[$studentId]);
                $result = AcademicModuleStudentResult::query()
                    ->where('academic_module_id', $module->id)
                    ->where('student_id', (int) $studentId)
                    ->lockForUpdate()
                    ->first();
                $contract = EnrollmentContract::query()
                    ->where('student_id', (int) $studentId)
                    ->where('program_id', $module->program_id)
                    ->where('status', 'enrolled')
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                if (! $contract) {
                    throw ValidationException::withMessages(['results' => 'Todos los estudiantes deben tener un contrato inscrito en el programa del módulo.']);
                }

                if (! $result?->account_charge_id) {
                    $lastCharge = $contract->charges()->lockForUpdate()->latest('period')->first();
                    $nextPeriod = $lastCharge ? CarbonImmutable::parse($lastCharge->period)->startOfMonth()->addMonth() : today()->startOfMonth();
                    $charge = $this->contracts->generateMonthlyCharge($contract, $nextPeriod, $concept);
                } else {
                    $result->charge()->update(['concept' => $concept]);
                }

                AcademicModuleStudentResult::updateOrCreate(
                    ['academic_module_id' => $module->id, 'student_id' => (int) $studentId],
                    [
                        'company_id' => $module->company_id,
                        'status' => $status,
                        'account_charge_id' => $result?->account_charge_id ?? $charge->id,
                        'finalized_by' => $user->id,
                        'finalized_at' => now(),
                    ],
                );
            }
        });
    }
}
