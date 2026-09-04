<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Enums\GradingFrequency;
use App\Models\AcademicModule;
use App\Models\ProgramGradingScheme;

final class TeacherTrackingService
{
    public function summarize(AcademicModule $module, ?ProgramGradingScheme $scheme): array
    {
        $components = $scheme?->components->values() ?? collect();
        $dailyComponents = $components->where('frequency', GradingFrequency::Daily)->values();
        $singleComponents = $components->where('frequency', GradingFrequency::Single)->values();
        $dailySkills = $dailyComponents->flatMap(fn ($component) => $component->skills->each(fn ($skill) => $skill->setRelation('component', $component)))->values();
        $singleSkills = $singleComponents->flatMap(fn ($component) => $component->skills->each(fn ($skill) => $skill->setRelation('component', $component)))->values();
        $displayComponents = $dailyComponents->concat($singleComponents)->values();
        $skills = $dailySkills->concat($singleSkills)->values();
        $selections = $module->classSessions->flatMap->gradingSkills;
        $observations = $module->classSessions->flatMap(fn ($session) => $session->academicObservations->each(
            fn ($observation) => $observation->setRelation('session', $session),
        ));

        $students = $module->studentAssignments->map(function ($assignment) use ($components, $dailyComponents, $dailySkills, $module, $observations, $selections, $singleSkills): array {
            $studentId = $assignment->student_id;
            $dailyScores = $dailySkills->mapWithKeys(function ($skill) use ($selections, $studentId): array {
                $grades = $selections->where('program_grading_skill_id', $skill->id)
                    ->flatMap->grades
                    ->where('student_id', $studentId);

                return [$skill->id => [
                    'average' => $grades->isEmpty() ? null : round((float) $grades->avg('score'), 2),
                    'count' => $grades->count(),
                ]];
            });
            $singleScores = $singleSkills->mapWithKeys(function ($skill) use ($module, $studentId): array {
                $grade = $module->singleGrades
                    ->where('program_grading_skill_id', $skill->id)
                    ->firstWhere('student_id', $studentId);

                return [$skill->id => [
                    'average' => $grade === null ? null : round((float) $grade->score, 2),
                    'count' => $grade === null ? 0 : 1,
                ]];
            });
            $scores = $dailyScores->union($singleScores);
            $componentAverages = $components->mapWithKeys(function ($component) use ($scores): array {
                $hasGrades = $component->skills->contains(
                    fn ($skill): bool => ($scores->get($skill->id)['count'] ?? 0) > 0,
                );
                $average = $component->skills->sum(
                    fn ($skill): float => (float) ($scores->get($skill->id)['average'] ?? 0) * (float) $skill->weight / 100,
                );

                return [$component->id => $hasGrades ? round($average, 2) : null];
            });

            $hasGrades = $scores->contains(fn (array $score): bool => $score['count'] > 0);
            $dailyWeight = (float) $dailyComponents->sum('weight');
            $weightedScore = $components->sum(function ($component) use ($scores): float {
                $componentScore = $component->skills->sum(
                    fn ($skill): float => (float) ($scores->get($skill->id)['average'] ?? 0) * (float) $skill->weight / 100,
                );

                return $componentScore * (float) $component->weight / 100;
            });
            $latestObservation = $observations->where('student_id', $studentId)->sortByDesc('session.class_date')->first();

            return [
                'student' => $assignment->student,
                'scores' => $scores,
                'component_averages' => $componentAverages,
                'daily_average' => $dailyScores->contains(fn (array $score): bool => $score['count'] > 0) && $dailyWeight > 0
                    ? round($dailyComponents->sum(function ($component) use ($scores): float {
                        $componentScore = $component->skills->sum(fn ($skill): float => (float) ($scores->get($skill->id)['average'] ?? 0) * (float) $skill->weight / 100);

                        return $componentScore * (float) $component->weight / 100;
                    }) * 100 / $dailyWeight, 2)
                    : null,
                'overall_score' => $hasGrades ? round($weightedScore, 2) : null,
                'latest_observation' => $latestObservation,
                'observation_count' => $observations->where('student_id', $studentId)->count(),
            ];
        });

        return compact('components', 'dailyComponents', 'singleComponents', 'displayComponents', 'skills', 'students');
    }
}
