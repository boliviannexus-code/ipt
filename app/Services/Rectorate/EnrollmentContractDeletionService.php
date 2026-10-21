<?php

namespace App\Services\Rectorate;

use App\Models\EnrollmentContract;
use App\Models\RectorateApplication;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EnrollmentContractDeletionService
{
    public function delete(User $actor, EnrollmentContract $contract): void
    {
        abort_unless(CompanyContext::isGlobalAdmin($actor), 403);

        DB::transaction(function () use ($actor, $contract): void {
            $contract = EnrollmentContract::query()->lockForUpdate()->findOrFail($contract->id);
            $application = RectorateApplication::withTrashed()->lockForUpdate()->findOrFail($contract->rectorate_application_id);
            $chargeIds = $contract->charges()->lockForUpdate()->pluck('id');

            if ($contract->payments()->exists()
                || $contract->creditApplications()->exists()
                || $contract->charges()->where('paid_amount', '>', 0)->exists()
                || DB::table('payment_allocations')->whereIn('account_charge_id', $chargeIds)->exists()) {
                throw ValidationException::withMessages([
                    'contract' => 'No se puede eliminar el contrato porque tiene cobros registrados.',
                ]);
            }

            // Only results directly dependent on this contract's charges belong to this deletion.
            DB::table('academic_module_student_results')->whereIn('account_charge_id', $chargeIds)->delete();
            $contract->charges()->delete();
            $contract->planHistory()->delete();
            // Database cascades also remove every recognized level belonging to these exams.
            $contract->placementExams()->delete();
            $contract->delete();
            $application->forceDelete();

            DB::afterCommit(static fn () => Log::notice('Contrato e inscripción eliminados por super administrador.', [
                'actor_id' => $actor->id,
                'company_id' => $contract->company_id,
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'application_id' => $application->id,
                'student_id' => $contract->student_id,
            ]));
        });
    }
}
