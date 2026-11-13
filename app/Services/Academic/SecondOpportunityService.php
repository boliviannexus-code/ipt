<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\AcademicModule;
use App\Models\AcademicModuleStudentResult;
use App\Models\AcademicSecondOpportunity;
use App\Models\EnrollmentContract;
use App\Models\ProgramLevel;
use App\Models\Student;
use App\Models\User;
use App\Services\Enrollment\EnrollmentCompletionService;
use App\Support\CompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SecondOpportunityService
{
    public function candidates(): Builder
    {
        return AcademicModuleStudentResult::query()
            ->where('status', 'failed')->whereNotNull('final_score')->whereNotNull('passing_score')->whereDoesntHave('secondOpportunity')
            ->whereHas('module', fn ($query) => $query->whereNotNull('closed_at'))
            ->whereHas('student', fn ($query) => $query->where('is_active', true)
                ->whereDoesntHave('moduleAssignments', fn ($query) => $query->whereNull('released_at')))
            ->whereExists(function ($query): void {
                $query->selectRaw('1')->from('enrollment_contracts as c')
                    ->join('academic_modules as m', 'm.id', '=', 'academic_module_student_results.academic_module_id')
                    ->whereColumn('c.student_id', 'academic_module_student_results.student_id')
                    ->whereColumn('c.company_id', 'academic_module_student_results.company_id')
                    ->whereColumn('c.program_id', 'm.program_id')
                    ->whereColumn('c.current_program_level_id', 'm.program_level_id')
                    ->whereColumn('c.confirmed_at', '<=', 'academic_module_student_results.finalized_at')
                    ->where('c.status', 'enrolled')
                    ->whereNotExists(function ($newer): void {
                        $newer->selectRaw('1')->from('enrollment_contracts as newer_contract')
                            ->whereColumn('newer_contract.student_id', 'c.student_id')
                            ->whereColumn('newer_contract.company_id', 'c.company_id')
                            ->whereColumn('newer_contract.program_id', 'c.program_id')
                            ->whereColumn('newer_contract.id', '>', 'c.id');
                    });
            })
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')->from('academic_module_student_assignments as a')
                    ->join('academic_modules as m', 'm.id', '=', 'academic_module_student_results.academic_module_id')
                    ->whereColumn('a.student_id', 'academic_module_student_results.student_id')
                    ->whereColumn('a.company_id', 'academic_module_student_results.company_id')
                    ->whereColumn('a.academic_module_id', '!=', 'm.id')
                    ->whereColumn('a.assigned_at', '>=', 'm.closed_at');
            })
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')->from('academic_module_student_results as r')
                    ->join('academic_modules as later_module', 'later_module.id', '=', 'r.academic_module_id')
                    ->join('academic_modules as m', 'm.id', '=', 'academic_module_student_results.academic_module_id')
                    ->whereColumn('r.student_id', 'academic_module_student_results.student_id')
                    ->whereColumn('r.company_id', 'academic_module_student_results.company_id')
                    ->whereColumn('later_module.program_id', 'm.program_id')
                    ->whereColumn('r.id', '!=', 'academic_module_student_results.id')
                    ->whereColumn('r.finalized_at', '>=', 'academic_module_student_results.finalized_at');
            });
    }

    public function context(AcademicModuleStudentResult $result): array
    {
        if (! $this->candidates()->whereKey($result->id)->exists()) {
            throw ValidationException::withMessages(['result' => 'Este resultado no admite 2T: debe estar reprobado, sin intento previo, con matrícula vigente en el mismo nivel y sin asignaciones activas ni historial posterior.']);
        }
        $result->loadMissing(['student', 'module.program', 'module.level']);
        $contract = EnrollmentContract::query()->where('student_id', $result->student_id)
            ->where('program_id', $result->module->program_id)->latest('id')->first();
        if (! $contract || $contract->status !== 'enrolled'
            || (int) $contract->current_program_level_id !== (int) $result->module->program_level_id
            || $contract->confirmed_at->gt($result->finalized_at)) {
            throw ValidationException::withMessages(['result' => 'La matrícula actual no corresponde al cierre y nivel que se desea evaluar.']);
        }
        $nextLevel = ProgramLevel::query()->where('program_id', $result->module->program_id)
            ->where('is_active', true)->where('position', '>', $result->module->level->position)->orderBy('position')->first();

        return compact('result', 'contract', 'nextLevel');
    }

    public function record(User $actor, AcademicModuleStudentResult $result, array $data): AcademicSecondOpportunity
    {
        abort_unless($actor->can('second-opportunities.manage') && (int) CompanyContext::id($actor) === (int) $result->company_id, 403);

        return DB::transaction(function () use ($actor, $result, $data): AcademicSecondOpportunity {
            Student::query()->whereKey($result->student_id)->lockForUpdate()->firstOrFail();
            AcademicModule::query()->whereKey($result->academic_module_id)->lockForUpdate()->firstOrFail();
            $result = AcademicModuleStudentResult::query()->lockForUpdate()->findOrFail($result->id);
            $context = $this->context($result);
            $contract = EnrollmentContract::query()->lockForUpdate()->findOrFail($context['contract']->id);
            // Recheck after all locks: concurrent assignment and duplicate submissions cannot pass.
            $context = $this->context($result);
            $score = round((float) $data['score'], 2);
            $status = $score >= (float) $result->passing_score ? 'approved' : 'failed';
            $attempt = AcademicSecondOpportunity::create([
                'company_id' => $result->company_id, 'academic_module_student_result_id' => $result->id,
                'enrollment_contract_id' => $contract->id, 'original_score' => $result->final_score,
                'original_status' => $result->status, 'score' => $score, 'status' => $status,
                'passing_score' => $result->passing_score, 'reason' => $data['reason'],
                'recorded_by' => $actor->id, 'recorded_at' => now(),
            ]);
            $result->update(['final_score' => $score, 'status' => $status]);
            if ($status === 'approved') {
                if ($context['nextLevel']) {
                    $contract->update(['current_program_level_id' => $context['nextLevel']->id]);
                } else {
                    app(EnrollmentCompletionService::class)->evaluate($contract);
                }
            }

            return $attempt;
        }, 3);
    }
}
