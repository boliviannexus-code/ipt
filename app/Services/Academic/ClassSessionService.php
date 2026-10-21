<?php

namespace App\Services\Academic;

use App\Models\AcademicModule;
use App\Models\ClassSession;
use App\Models\Personnel;
use App\Models\ProgramGradingScheme;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ClassSessionService
{
    public function start(AcademicModule $module, Personnel $teacher, User $user, string $classDate): ClassSession
    {
        return DB::transaction(function () use ($module, $teacher, $user, $classDate): ClassSession {
            $module = AcademicModule::query()->lockForUpdate()->findOrFail($module->id);
            if ($module->closed_at !== null || $module->studentResults()->exists()) {
                throw ValidationException::withMessages(['class_date' => 'Este curso ya fue cerrado y no admite nuevas clases.']);
            }
            if ($classDate < $module->start_date->toDateString() || $classDate > $module->end_date->toDateString()) {
                throw ValidationException::withMessages(['class_date' => 'La fecha de la clase debe estar dentro de la vigencia del curso.']);
            }
            if ($module->classSessions()->whereDate('class_date', $classDate)->exists()) {
                throw ValidationException::withMessages(['class_date' => 'Ya existe una clase registrada para este curso en la fecha seleccionada.']);
            }
            $schemeId = $module->singleGrades()->with('skill.component')->first()?->skill?->component?->program_grading_scheme_id
                ?? $module->classSessions()->whereNotNull('program_grading_scheme_id')->latest('class_date')->value('program_grading_scheme_id')
                ?? $module->configuredGradingScheme()?->id;

            $session = ClassSession::create([
                'academic_module_id' => $module->id,
                'class_date' => $classDate,
                'company_id' => $module->company_id,
                'program_grading_scheme_id' => $schemeId,
                'personnel_id' => $teacher->id,
                'started_by' => $user->id,
                'started_at' => now(),
            ]);
            $scheme = $schemeId ? ProgramGradingScheme::query()->with('components.skills')->find($schemeId) : null;
            foreach (AttendanceGradingPolicy::skills($scheme) as $skill) {
                $session->gradingSkills()->create([
                    'company_id' => $session->company_id, 'program_grading_skill_id' => $skill->id,
                    'selected_by' => $user->id, 'selected_at' => now(),
                ]);
            }

            return $session;
        });
    }
}
