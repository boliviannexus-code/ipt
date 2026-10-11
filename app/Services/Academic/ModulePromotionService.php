<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\AcademicModule;
use App\Models\AcademicModuleStudentAssignment;
use App\Models\AcademicModuleTeacherAssignment;
use App\Models\EnrollmentContract;
use App\Models\ProgramLevel;
use App\Models\User;
use App\Services\Enrollment\ContractService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ModulePromotionService
{
    public function __construct(private readonly ContractService $contracts) {}

    public function context(AcademicModule $module): array
    {
        abort_if($module->closed_at === null, 409, 'Solo se pueden promover módulos cerrados.');
        $module->loadMissing(['program', 'level', 'currentTeacherAssignment.personnel', 'studentAssignments.student']);
        $targetLevel = ProgramLevel::query()
            ->where('program_id', $module->program_id)
            ->where('is_active', true)
            ->where('position', '>', $module->level->position)
            ->orderBy('position')
            ->first();
        abort_if($targetLevel === null, 409, 'El programa no tiene un siguiente nivel activo configurado.');

        return [
            'targetLevel' => $targetLevel,
            'students' => $this->studentOptions($module, $targetLevel),
        ];
    }

    public function promote(User $user, AcademicModule $source, array $dates, array $studentIds): AcademicModule
    {
        return DB::transaction(function () use ($dates, $source, $studentIds, $user): AcademicModule {
            $source = AcademicModule::query()->with(['level', 'currentTeacherAssignment'])->lockForUpdate()->findOrFail($source->id);
            $context = $this->context($source);
            $targetLevel = $context['targetLevel'];
            $selectedIds = collect($studentIds)->map(fn ($id): int => (int) $id)->unique()->values();
            $eligibleIds = $context['students']->where('eligible', true)->pluck('student.id');
            if ($selectedIds->isEmpty() || $selectedIds->diff($eligibleIds)->isNotEmpty()) {
                throw ValidationException::withMessages(['student_ids' => 'Uno o más estudiantes ya no están disponibles o no pertenecen al nivel destino.']);
            }

            $moduleName = 'Módulo '.$targetLevel->name;
            if (AcademicModule::query()->where('program_id', $source->program_id)->where('program_level_id', $targetLevel->id)->where('name', $moduleName)->exists()) {
                $moduleName .= ' - '.CarbonImmutable::parse($dates['start_date'])->format('d-m-Y');
            }
            $newModule = AcademicModule::query()->create([
                'company_id' => $source->company_id,
                'program_id' => $source->program_id,
                'program_level_id' => $targetLevel->id,
                'name' => $moduleName,
                'modality' => $source->modality,
                'starts_at' => $source->starts_at,
                'ends_at' => $source->ends_at,
                'start_date' => $dates['start_date'],
                'end_date' => $dates['end_date'],
            ]);
            if ($source->currentTeacherAssignment !== null) {
                AcademicModuleTeacherAssignment::query()->create([
                    'company_id' => $source->company_id,
                    'academic_module_id' => $newModule->id,
                    'personnel_id' => $source->currentTeacherAssignment->personnel_id,
                    'assigned_at' => now(),
                ]);
            }

            foreach ($selectedIds as $studentId) {
                $contract = EnrollmentContract::query()
                    ->where('student_id', $studentId)
                    ->where('program_id', $source->program_id)
                    ->where('status', 'enrolled')
                    ->where('current_program_level_id', $targetLevel->id)
                    ->lockForUpdate()
                    ->latest('id')
                    ->firstOrFail();
                AcademicModuleStudentAssignment::query()->create([
                    'company_id' => $source->company_id,
                    'academic_module_id' => $newModule->id,
                    'student_id' => $studentId,
                    'assigned_by' => $user->id,
                    'assigned_at' => now(),
                ]);
                $lastCharge = $contract->charges()->lockForUpdate()->latest('period')->first();
                $period = $lastCharge ? CarbonImmutable::parse($lastCharge->period)->startOfMonth()->addMonth() : today()->startOfMonth();
                $this->contracts->generateMonthlyCharge($contract, $period, 'Mensualidad - '.$newModule->name);
            }

            return $newModule;
        });
    }

    private function studentOptions(AcademicModule $module, ProgramLevel $targetLevel): Collection
    {
        $studentIds = $module->studentAssignments->pluck('student_id');
        $activeStudentIds = AcademicModuleStudentAssignment::query()->whereIn('student_id', $studentIds)->whereNull('released_at')->pluck('student_id');
        $contracts = EnrollmentContract::query()
            ->whereIn('student_id', $studentIds)
            ->where('program_id', $module->program_id)
            ->where('status', 'enrolled')
            ->latest('id')
            ->get()
            ->unique('student_id')
            ->keyBy('student_id');

        return $module->studentAssignments->map(function ($assignment) use ($activeStudentIds, $contracts, $targetLevel): array {
            $contract = $contracts->get($assignment->student_id);
            $hasActiveModule = $activeStudentIds->contains($assignment->student_id);
            $hasTargetLevel = $contract?->current_program_level_id === $targetLevel->id;

            return [
                'student' => $assignment->student,
                'eligible' => ! $hasActiveModule && $hasTargetLevel,
                'reason' => $hasActiveModule ? 'Ya está asignado a otro módulo' : (! $hasTargetLevel ? 'No pertenece al nivel destino' : null),
            ];
        })->unique('student.id')->values();
    }
}
