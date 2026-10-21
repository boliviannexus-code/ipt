<?php

namespace App\Services\Enrollment;

use App\Models\AcademicModuleStudentResult;
use App\Models\EnrollmentContract;
use App\Models\PlacementExamLevel;
use App\Models\ProgramLevel;
use Illuminate\Support\Facades\DB;

class EnrollmentCompletionService
{
    public function evaluate(EnrollmentContract $contract): void
    {
        DB::transaction(function () use ($contract): void {
            $contract = EnrollmentContract::query()->lockForUpdate()->findOrFail($contract->id);
            if (! in_array($contract->status, ['enrolled', 'inactive'], true)) {
                return;
            }
            $levels = ProgramLevel::query()->where('program_id', $contract->program_id)->where('is_active', true)->orderBy('position')->get();
            $last = $levels->last();
            if (! $last || (int) $contract->current_program_level_id !== (int) $last->id) {
                return;
            }
            $results = AcademicModuleStudentResult::query()->where('student_id', $contract->student_id)->where('status', 'approved')
                ->whereHas('module', fn ($query) => $query->where('program_id', $contract->program_id))
                ->with('module')->get();
            $passedLast = $results->contains(fn ($result): bool => (int) $result->module->program_level_id === (int) $last->id
                && $result->finalized_at->greaterThanOrEqualTo($contract->confirmed_at));
            if (! $passedLast) {
                return;
            }
            $recognized = PlacementExamLevel::query()->whereHas('exam', fn ($query) => $query->where('enrollment_contract_id', $contract->id))->pluck('program_level_id');
            $approved = $results->pluck('module.program_level_id')->merge($recognized)->unique();
            if ($levels->pluck('id')->diff($approved)->isNotEmpty()
                || $contract->charges()->where('status', '!=', 'cancelled')->whereColumn('paid_amount', '<', 'amount')->exists()) {
                return;
            }
            $contract->update(['status' => 'finished', 'completed_at' => now()]);
        });
    }
}
