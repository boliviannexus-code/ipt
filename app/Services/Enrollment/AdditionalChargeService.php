<?php

namespace App\Services\Enrollment;

use App\Models\AccountCharge;
use App\Models\AdditionalService;
use App\Models\EnrollmentContract;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdditionalChargeService
{
    public function record(User $actor, Student $student, array $data): AccountCharge
    {
        abort_unless($actor->can('additional-charges.manage'), 403);

        return DB::transaction(function () use ($actor, $student, $data): AccountCharge {
            $contract = EnrollmentContract::query()->where('student_id', $student->id)
                ->where('company_id', $student->company_id)->lockForUpdate()->findOrFail($data['enrollment_contract_id']);
            $existing = AccountCharge::query()->where('submission_token', $data['submission_token'])->first();
            if ($existing) {
                abort_unless($existing->enrollment_contract_id === $contract->id && $existing->created_by === $actor->id, 409);

                return $existing;
            }
            if ($contract->status === 'cancelled') {
                throw ValidationException::withMessages(['enrollment_contract_id' => 'No se pueden agregar cargos a un contrato cancelado.']);
            }
            $service = AdditionalService::query()->where('company_id', $contract->company_id)
                ->where('is_active', true)->lockForUpdate()->find($data['additional_service_id']);
            if (! $service) {
                throw ValidationException::withMessages(['additional_service_id' => 'Seleccione un servicio adicional activo de la empresa.']);
            }

            return $this->createCharge($actor, $contract, $service, $data['due_date'], $data['submission_token']);
        });
    }

    private function createCharge(User $actor, EnrollmentContract $contract, AdditionalService $service, string $dueDate, ?string $token = null): AccountCharge
    {
        return AccountCharge::create([
            'company_id' => $contract->company_id, 'enrollment_contract_id' => $contract->id,
            'plan_id' => $contract->plan_id, 'period' => null, 'charge_type' => 'additional',
            'additional_service_id' => $service->id, 'concept' => $service->title, 'detail' => $service->detail,
            'due_date' => $dueDate, 'amount' => $service->cost, 'paid_amount' => 0,
            'status' => (float) $service->cost === 0.0 ? 'paid' : 'pending',
            'created_by' => $actor->id, 'submission_token' => $token,
        ]);
    }
}
