<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\ClassSession;
use App\Models\ClassSessionGradingSkill;
use App\Models\StudentAcademicObservation;
use App\Models\User;

final class DailyGradeService
{
    public function saveGrade(ClassSession $session, User $user, int $skillId, int $studentId, float $score): void
    {
        $sessionSkill = ClassSessionGradingSkill::updateOrCreate(
            ['class_session_id' => $session->id, 'program_grading_skill_id' => $skillId],
            ['company_id' => $session->company_id, 'selected_by' => $user->id, 'selected_at' => now()],
        );
        $sessionSkill->grades()->updateOrCreate(
            ['student_id' => $studentId],
            ['company_id' => $session->company_id, 'score' => $score, 'graded_by' => $user->id, 'graded_at' => now()],
        );
    }

    public function saveObservation(ClassSession $session, User $user, int $studentId, ?string $observation): void
    {
        $value = trim((string) $observation);
        if ($value === '') {
            $session->academicObservations()->where('student_id', $studentId)->delete();

            return;
        }

        StudentAcademicObservation::updateOrCreate(
            ['class_session_id' => $session->id, 'student_id' => $studentId],
            ['company_id' => $session->company_id, 'observation' => $value, 'recorded_by' => $user->id, 'recorded_at' => now()],
        );
    }

    public function save(ClassSession $session, User $user, array $selectedSkillIds, array $grades): void
    {
        $session->gradingSkills()->whereNotIn('program_grading_skill_id', $selectedSkillIds ?: [0])->delete();

        foreach ($selectedSkillIds as $skillId) {
            $sessionSkill = ClassSessionGradingSkill::updateOrCreate(
                ['class_session_id' => $session->id, 'program_grading_skill_id' => $skillId],
                ['company_id' => $session->company_id, 'selected_by' => $user->id, 'selected_at' => now()],
            );

            foreach ($grades[$skillId] as $studentId => $score) {
                $sessionSkill->grades()->updateOrCreate(
                    ['student_id' => (int) $studentId],
                    ['company_id' => $session->company_id, 'score' => $score, 'graded_by' => $user->id, 'graded_at' => now()],
                );
            }
        }
    }

    public function saveObservations(ClassSession $session, User $user, array $studentIds, array $observations): void
    {
        foreach ($studentIds as $studentId) {
            $this->saveObservation($session, $user, (int) $studentId, $observations[$studentId] ?? null);
        }
    }
}
