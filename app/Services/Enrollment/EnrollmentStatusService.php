<?php

namespace App\Services\Enrollment;

use App\Models\EnrollmentContract;
use App\Models\EnrollmentStatusChange;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnrollmentStatusService
{
    public const CHANGEABLE = ['enrolled', 'inactive', 'suspended', 'abandoned'];

    public const TARGETS = ['suspended' => 'SUSPENDIDO', 'abandoned' => 'ABANDONO', 'withdrawn' => 'BAJA'];

    public function record(User $actor, Student $student, array $data): EnrollmentStatusChange
    {
        abort_unless($actor->can('students.manage'), 403);

        return DB::transaction(function () use ($actor, $student, $data): EnrollmentStatusChange {
            Student::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();
            $contract = EnrollmentContract::query()->where('student_id', $student->id)->where('company_id', $student->company_id)
                ->lockForUpdate()->findOrFail($data['enrollment_contract_id']);
            $existing = EnrollmentStatusChange::query()->where('submission_token', $data['submission_token'])->first();
            if ($existing) {
                abort_unless($existing->enrollment_contract_id === $contract->id && $existing->recorded_by === $actor->id, 409);

                return $existing;
            }
            if (! in_array($contract->status, self::CHANGEABLE, true)) {
                throw ValidationException::withMessages(['enrollment_contract_id' => 'Esta matrícula no admite cambios. BAJA es un estado definitivo.']);
            }
            if (! array_key_exists($data['status'], self::TARGETS) || $contract->status === $data['status']) {
                throw ValidationException::withMessages(['status' => 'Seleccione un estado diferente al actual.']);
            }
            if ($contract->status_registered_on && $data['registered_on'] < $contract->status_registered_on->toDateString()) {
                throw ValidationException::withMessages(['registered_on' => 'La fecha no puede ser anterior al último cambio de estado.']);
            }
            $reactivationDate = $data['status'] === 'suspended' ? $data['reactivation_date'] : null;
            $change = $contract->statusChanges()->create([
                'company_id' => $contract->company_id, 'previous_status' => $contract->status,
                'status' => $data['status'], 'observation' => $data['observation'],
                'registered_on' => $data['registered_on'], 'reactivation_date' => $reactivationDate,
                'recorded_by' => $actor->id, 'submission_token' => $data['submission_token'],
            ]);
            $contract->update(['status' => $data['status'], 'status_registered_on' => $data['registered_on'], 'reactivation_date' => $reactivationDate]);
            $student->moduleAssignments()->whereNull('released_at')
                ->whereHas('module', fn ($query) => $query->where('program_id', $contract->program_id))
                ->update(['released_at' => now()]);

            return $change;
        });
    }
}
