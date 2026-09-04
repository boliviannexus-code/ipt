<?php

namespace App\Services\Academic;

use App\Models\AcademicModule;
use App\Models\ProgramGradingScheme;
use App\Models\Student;

class StudentKardexService
{
    public function __construct(private readonly TeacherTrackingService $tracking) {}

    public function build(Student $student): array
    {
        $student->loadMissing([
            'company',
            'campus',
            'contracts.program',
            'contracts.plan',
            'applications.customer',
            'moduleAssignments.module.program',
            'moduleAssignments.module.level',
            'moduleAssignments.module.currentTeacherAssignment.personnel',
            'moduleAssignments.module.studentAssignments.student',
            'moduleAssignments.module.singleGrades.skill.component',
            'moduleAssignments.module.classSessions.gradingSkills.skill.component',
            'moduleAssignments.module.classSessions.gradingSkills.grades',
            'moduleAssignments.module.classSessions.academicObservations.student',
            'moduleResults',
        ]);

        $results = $student->moduleResults->keyBy('academic_module_id');
        $modules = $student->moduleAssignments->pluck('module')->filter();
        $schemeIdsByModule = $modules->mapWithKeys(fn ($module): array => [
            $module->id => $module->singleGrades->first()?->skill?->component?->program_grading_scheme_id
                ?? $module->classSessions->whereNotNull('program_grading_scheme_id')->sortByDesc('class_date')->first()?->program_grading_scheme_id,
        ]);
        $pinnedSchemeIds = $schemeIdsByModule->filter()->unique()->values();
        $pinnedSchemes = $pinnedSchemeIds->isEmpty()
            ? collect()
            : ProgramGradingScheme::query()->with('components.skills')->whereIn('id', $pinnedSchemeIds)->get()->keyBy('id');
        $activeSchemes = ProgramGradingScheme::query()
            ->with('components.skills')
            ->whereIn('program_id', $modules->pluck('program_id')->unique())
            ->where('is_active', true)
            ->get()
            ->keyBy('program_id');
        $academicRows = $student->moduleAssignments
            ->sortBy(fn ($assignment) => $assignment->module?->start_date?->timestamp ?? PHP_INT_MAX)
            ->map(function ($assignment) use ($activeSchemes, $pinnedSchemes, $results, $schemeIdsByModule, $student): array {
                $module = $assignment->module;
                $schemeId = $schemeIdsByModule->get($module->id);
                $scheme = $schemeId ? $pinnedSchemes->get($schemeId) : $activeSchemes->get($module->program_id);
                $summary = $this->tracking->summarize($module, $scheme);
                $studentSummary = $summary['students']->first(
                    fn (array $row): bool => $row['student']->id === $student->id,
                );

                return [
                    'assignment' => $assignment,
                    'module' => $module,
                    'result' => $results->get($module->id),
                    'grading' => [
                        'scheme_version' => $scheme?->version,
                        'passing_score' => $scheme === null ? null : (float) $scheme->passing_score,
                        'overall_score' => $studentSummary['overall_score'] ?? null,
                        'components' => $summary['displayComponents']->map(fn ($component): array => [
                            'name' => $component->name,
                            'weight' => (float) $component->weight,
                            'frequency' => $component->frequency->value,
                            'average' => $studentSummary === null ? null : $studentSummary['component_averages']->get($component->id),
                        ]),
                    ],
                ];
            })
            ->values();
        $holder = $student->applications->sortByDesc('id')->first();

        return compact('student', 'academicRows', 'holder');
    }

    public function dailyDetails(Student $student, AcademicModule $module): array
    {
        abort_unless(
            $student->moduleAssignments()->where('academic_module_id', $module->id)->exists(),
            404,
        );

        $module->loadMissing([
            'program',
            'level',
            'currentTeacherAssignment.personnel',
            'classSessions' => fn ($query) => $query->orderByDesc('class_date')->with([
                'teacher',
                'academicObservations' => fn ($query) => $query->where('student_id', $student->id),
                'gradingSkills.skill.component',
                'gradingSkills.grades' => fn ($query) => $query->where('student_id', $student->id),
            ]),
        ]);

        $dailyRows = $module->classSessions->map(function ($session): array {
            $evaluations = $session->gradingSkills
                ->map(function ($selection): ?array {
                    $grade = $selection->grades->first();

                    if ($grade === null) {
                        return null;
                    }

                    return [
                        'component' => $selection->skill->component->name,
                        'skill' => $selection->skill->name,
                        'score' => (float) $grade->score,
                    ];
                })
                ->filter()
                ->values();

            return [
                'session' => $session,
                'evaluations' => $evaluations,
                'observation' => $session->academicObservations->first(),
            ];
        })->values();

        return compact('student', 'module', 'dailyRows');
    }
}
