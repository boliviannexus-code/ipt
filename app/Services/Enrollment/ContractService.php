<?php

namespace App\Services\Enrollment;

use App\Models\AccountCharge;
use App\Models\ContractPlanHistory;
use App\Models\EnrollmentContract;
use App\Models\Plan;
use App\Models\RectorateApplication;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ContractService
{
    public function createForApplication(RectorateApplication $application): EnrollmentContract
    {
        $existing = EnrollmentContract::withoutGlobalScope('company')
            ->where('rectorate_application_id', $application->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $plan = Plan::withoutGlobalScope('company')->findOrFail($application->plan_id);
        DB::table('program_contract_sequences')->insertOrIgnore([
            'program_id' => $application->program_id,
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $sequence = DB::table('program_contract_sequences')
            ->where('program_id', $application->program_id)
            ->lockForUpdate()
            ->first();
        $number = ((int) $sequence->last_number) + 1;
        DB::table('program_contract_sequences')
            ->where('program_id', $application->program_id)
            ->update(['last_number' => $number, 'updated_at' => now()]);

        $contract = EnrollmentContract::withoutGlobalScope('company')->create([
            'company_id' => $application->company_id,
            'campus_id' => $application->campus_id,
            'account_number' => $application->account_number,
            'rectorate_application_id' => $application->id,
            'student_id' => $application->student_id,
            'program_id' => $application->program_id,
            'plan_id' => $plan->id,
            'contract_number' => $number,
            'monthly_amount' => $plan->monthly_cost,
            'status' => 'pre_enrolled',
            'confirmed_at' => now(),
        ]);

        ContractPlanHistory::create([
            'enrollment_contract_id' => $contract->id,
            'plan_id' => $plan->id,
            'monthly_amount' => $plan->monthly_cost,
            'effective_from' => today(),
            'created_at' => now(),
        ]);
        $this->generateMonthlyCharge($contract, today(), 'Inscripción');

        return $contract->refresh();
    }

    public function changePlan(EnrollmentContract $contract, Plan $plan, CarbonInterface $effectiveFrom): EnrollmentContract
    {
        if ((int) $contract->company_id !== (int) $plan->company_id || ! $plan->programs()->whereKey($contract->program_id)->exists()) {
            throw new \LogicException('El plan no pertenece a la empresa y programa del contrato.');
        }

        return DB::transaction(function () use ($contract, $plan, $effectiveFrom): EnrollmentContract {
            $contract = EnrollmentContract::query()->lockForUpdate()->findOrFail($contract->id);
            $contract->update(['plan_id' => $plan->id, 'monthly_amount' => $plan->monthly_cost]);
            ContractPlanHistory::updateOrCreate(
                ['enrollment_contract_id' => $contract->id, 'effective_from' => $effectiveFrom->toDateString()],
                ['plan_id' => $plan->id, 'monthly_amount' => $plan->monthly_cost, 'created_at' => now()],
            );

            return $contract->refresh();
        });
    }

    public function generateMonthlyCharge(EnrollmentContract $contract, CarbonInterface $period, string $concept = 'Mensualidad'): AccountCharge
    {
        $period = $period->copy()->startOfMonth();

        return AccountCharge::withoutGlobalScope('company')->firstOrCreate(
            ['enrollment_contract_id' => $contract->id, 'period' => $period->toDateString()],
            [
                'company_id' => $contract->company_id,
                'plan_id' => $contract->plan_id,
                'concept' => $concept,
                'due_date' => $period->toDateString(),
                'amount' => $contract->monthly_amount,
                'paid_amount' => 0,
                'status' => 'pending',
            ],
        );
    }
}
