<?php

namespace App\Services\Academic;

use App\Models\AcademicModule;
use App\Models\EnrollmentContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudentAttendanceRegularityService
{
    public const ABSENCE_LIMIT = 3;

    public function absenceCounts(AcademicModule $module): Collection
    {
        return DB::table('student_daily_grades as grades')
            ->join('class_session_grading_skills as selections', 'selections.id', '=', 'grades.class_session_grading_skill_id')
            ->join('class_sessions as sessions', 'sessions.id', '=', 'selections.class_session_id')
            ->join('program_grading_skills as skills', 'skills.id', '=', 'selections.program_grading_skill_id')
            ->join('program_grading_components as components', 'components.id', '=', 'skills.program_grading_component_id')
            ->where('sessions.academic_module_id', $module->id)->where('sessions.company_id', $module->company_id)
            ->where('grades.company_id', $module->company_id)->whereNotNull('sessions.ended_at')
            ->whereRaw('LOWER(TRIM(components.name)) = ?', ['attendance'])
            ->where('components.frequency', 'daily')->where('components.scoring_method', 'simple')
            ->where('grades.score', 0)->groupBy('grades.student_id')
            ->selectRaw('grades.student_id, COUNT(DISTINCT sessions.id) as absences')->pluck('absences', 'student_id');
    }

    public function evaluate(AcademicModule $module): void
    {
        DB::transaction(function () use ($module): void {
            $module = AcademicModule::query()->lockForUpdate()->findOrFail($module->id);
            $studentIds = $this->absenceCounts($module)->filter(fn ($count): bool => (int) $count >= self::ABSENCE_LIMIT)->keys();
            $assignedIds = $module->studentAssignments()->whereNull('released_at')->whereIn('student_id', $studentIds)->pluck('student_id');
            foreach ($assignedIds as $studentId) {
                // Matches the contract resolution used for academic completion and promotion.
                $contract = EnrollmentContract::query()->where('company_id', $module->company_id)
                    ->where('program_id', $module->program_id)->where('student_id', $studentId)
                    ->whereIn('status', ['enrolled', 'inactive'])->latest('id')->lockForUpdate()->first();
                if ($contract?->status === 'enrolled') {
                    $contract->update([
                        'status' => 'inactive', 'attendance_inactive_at' => now(),
                        'attendance_inactive_module_id' => $module->id,
                    ]);
                }
            }
        });
    }
}
