<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\AcademicModule;
use App\Models\AcademicModuleStudentResult;
use App\Models\EnrollmentContract;
use App\Models\ProgramGradingScheme;
use App\Models\ProgramLevel;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ModuleCompletionService
{
    public function __construct(private readonly TeacherTrackingService $tracking) {}

    public function preview(AcademicModule $module): array
    {
        $module->loadMissing([
            'studentAssignments.student',
            'singleGrades.skill.component',
            'classSessions' => fn ($query) => $query->orderBy('class_date')->with([
                'gradingSkills.skill.component',
                'gradingSkills.grades',
                'academicObservations.student',
            ]),
        ]);
        $schemeId = $module->singleGrades->first()?->skill?->component?->program_grading_scheme_id
            ?? $module->classSessions->whereNotNull('program_grading_scheme_id')->sortByDesc('class_date')->first()?->program_grading_scheme_id;
        $scheme = $schemeId
            ? ProgramGradingScheme::query()->with('components.skills')->find($schemeId)
            : $module->configuredGradingScheme();

        return ['scheme' => $scheme, ...$this->tracking->summarize($module, $scheme)];
    }

    public function complete(User $user, AcademicModule $module): void
    {
        DB::transaction(function () use ($user, $module): void {
            $module = AcademicModule::query()->lockForUpdate()->findOrFail($module->id);
            if ($module->closed_at !== null) {
                throw ValidationException::withMessages(['results' => 'El módulo ya fue cerrado.']);
            }

            $preview = $this->preview($module);
            /** @var ProgramGradingScheme|null $scheme */
            $scheme = $preview['scheme'];
            if ($scheme === null || $preview['components']->isEmpty()) {
                throw ValidationException::withMessages(['results' => 'El programa no tiene ponderaciones activas para calcular los resultados.']);
            }
            if ($preview['students']->isEmpty()) {
                throw ValidationException::withMessages(['results' => 'El módulo no tiene estudiantes asignados para cerrar el curso.']);
            }
            if ($preview['students']->contains(fn (array $row): bool => $row['overall_score'] === null)) {
                throw ValidationException::withMessages(['results' => 'Todos los estudiantes deben tener al menos una ponderación registrada antes de cerrar el curso.']);
            }

            $finalizedAt = now();
            $currentLevel = ProgramLevel::query()->findOrFail($module->program_level_id);
            foreach ($preview['students'] as $row) {
                $finalScore = (float) $row['overall_score'];
                $passingScore = (float) $scheme->passing_score;
                $isApproved = $finalScore >= $passingScore;
                AcademicModuleStudentResult::query()->create([
                    'company_id' => $module->company_id,
                    'academic_module_id' => $module->id,
                    'student_id' => $row['student']->id,
                    'program_grading_scheme_id' => $scheme->id,
                    'status' => $isApproved ? 'approved' : 'failed',
                    'final_score' => $finalScore,
                    'passing_score' => $passingScore,
                    'grading_details' => $preview['displayComponents']->map(fn ($component): array => [
                        'name' => $component->name,
                        'weight' => (float) $component->weight,
                        'frequency' => $component->frequency->value,
                        'average' => $row['component_averages']->get($component->id),
                    ])->all(),
                    'finalized_by' => $user->id,
                    'finalized_at' => $finalizedAt,
                ]);

                $module->studentAssignments()
                    ->where('student_id', $row['student']->id)
                    ->whereNull('released_at')
                    ->update(['released_at' => $finalizedAt]);

                if ($isApproved) {
                    $contract = EnrollmentContract::query()
                        ->where('student_id', $row['student']->id)
                        ->where('program_id', $module->program_id)
                        ->where('status', 'enrolled')
                        ->lockForUpdate()
                        ->latest('id')
                        ->first();
                    if ($contract === null) {
                        throw ValidationException::withMessages(['results' => 'Todo estudiante aprobado debe tener una matrícula activa en el programa.']);
                    }

                    $nextLevelId = ProgramLevel::query()
                        ->where('program_id', $module->program_id)
                        ->where('is_active', true)
                        ->where('position', '>', $currentLevel->position)
                        ->orderBy('position')
                        ->value('id');
                    if ($nextLevelId !== null) {
                        $contract->update(['current_program_level_id' => $nextLevelId]);
                    }
                }
            }

            $module->update(['closed_at' => $finalizedAt, 'closed_by' => $user->id]);
        });
    }
}
