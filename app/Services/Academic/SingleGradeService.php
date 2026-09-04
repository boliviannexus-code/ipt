<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\AcademicModule;
use App\Models\StudentSingleGrade;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class SingleGradeService
{
    public function save(AcademicModule $module, User $user, array $grades): void
    {
        DB::transaction(function () use ($grades, $module, $user): void {
            foreach ($grades as $skillId => $studentGrades) {
                foreach ($studentGrades as $studentId => $score) {
                    StudentSingleGrade::updateOrCreate(
                        [
                            'academic_module_id' => $module->id,
                            'program_grading_skill_id' => (int) $skillId,
                            'student_id' => (int) $studentId,
                        ],
                        [
                            'company_id' => $module->company_id,
                            'score' => $score,
                            'graded_by' => $user->id,
                            'graded_at' => now(),
                        ],
                    );
                }
            }
        });
    }
}
