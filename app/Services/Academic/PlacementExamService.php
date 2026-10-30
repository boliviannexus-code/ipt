<?php

namespace App\Services\Academic;

use App\Models\AcademicModuleStudentResult;
use App\Models\EnrollmentContract;
use App\Models\Personnel;
use App\Models\PlacementExam;
use App\Models\PlacementExamLevel;
use App\Models\ProgramLevel;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlacementExamService
{
    public function teachers(int $companyId): Collection
    {
        return Personnel::query()->availableForTeaching($companyId)
            ->orderBy('first_name')->orderBy('paternal_surname')->get();
    }

    public function currentLevel(EnrollmentContract $contract): ?ProgramLevel
    {
        return $contract->currentProgramLevel
            ?? ProgramLevel::query()->where('program_id', $contract->program_id)->orderBy('position')->first();
    }

    public function preview(Student $student, EnrollmentContract $contract, ProgramLevel $target): Collection
    {
        $approved = AcademicModuleStudentResult::query()->where('student_id', $student->id)->where('status', 'approved')
            ->whereHas('module', fn ($query) => $query->where('program_id', $contract->program_id))
            ->with('module')->get()->pluck('module.program_level_id');
        $recognized = PlacementExamLevel::query()->where('student_id', $student->id)
            ->where('program_id', $contract->program_id)
            ->whereHas('exam', fn ($query) => $query->where('company_id', $contract->company_id))
            ->pluck('program_level_id');

        return ProgramLevel::query()->where('program_id', $contract->program_id)->where('position', '<', $target->position)
            ->orderBy('position')->get()->map(fn ($level): array => [
                'level' => $level,
                'preserved' => $approved->contains($level->id) || $recognized->contains($level->id),
            ]);
    }

    public function record(User $actor, Student $student, array $data): PlacementExam
    {
        abort_unless($actor->can('students.manage'), 403);

        return DB::transaction(function () use ($actor, $student, $data): PlacementExam {
            // Same student lock as ordinary module assignment: eligibility cannot change mid-save.
            $student = Student::query()->lockForUpdate()->findOrFail($student->id);
            $contract = EnrollmentContract::query()->where('student_id', $student->id)
                ->where('company_id', $student->company_id)->lockForUpdate()->findOrFail($data['enrollment_contract_id']);
            $existing = PlacementExam::query()->where('submission_token', $data['submission_token'])->first();
            if ($existing) {
                abort_unless($existing->student_id === $student->id
                    && $existing->enrollment_contract_id === $contract->id
                    && $existing->recorded_by === $actor->id, 409);

                return $existing;
            }
            if (! $student->is_active || $contract->status !== 'enrolled' || $student->moduleAssignments()->whereNull('released_at')->exists()) {
                throw ValidationException::withMessages(['student' => 'El estudiante debe estar inscrito, activo y sin módulo abierto.']);
            }
            if ((int) ($data['expected_current_level_id'] ?? 0) !== (int) $contract->current_program_level_id) {
                throw ValidationException::withMessages(['target_program_level_id' => 'El nivel del estudiante cambió. Recargue el formulario antes de registrar el examen.']);
            }
            $current = $this->currentLevel($contract);
            $target = ProgramLevel::query()->where('program_id', $contract->program_id)
                ->where('company_id', $student->company_id)->where('is_active', true)->find($data['target_program_level_id']);
            if (! $target || ! $current || $target->position <= $current->position) {
                throw ValidationException::withMessages(['target_program_level_id' => 'Seleccione un nivel activo superior al actual dentro del programa.']);
            }
            $teacher = $this->teachers((int) $student->company_id)->firstWhere('id', (int) $data['teacher_id']);
            if (! $teacher) {
                throw ValidationException::withMessages(['teacher_id' => 'Seleccione personal activo con cargo académico o del área Académica de la empresa.']);
            }
            $preview = $this->preview($student, $contract, $target);
            $exam = PlacementExam::create([
                'company_id' => $student->company_id, 'student_id' => $student->id,
                'enrollment_contract_id' => $contract->id, 'program_id' => $contract->program_id,
                'previous_program_level_id' => $current->id, 'target_program_level_id' => $target->id,
                'previous_level_name' => $current->name, 'target_level_name' => $target->name,
                'score' => $data['score'], 'teacher_id' => $teacher->id, 'observation' => $data['observation'],
                'recorded_by' => $actor->id, 'examined_at' => now(), 'submission_token' => $data['submission_token'],
            ]);
            foreach ($preview->where('preserved', false) as $row) {
                $exam->levels()->create([
                    'student_id' => $student->id, 'program_id' => $contract->program_id,
                    'program_level_id' => $row['level']->id, 'level_name' => $row['level']->name,
                    'position' => $row['level']->position,
                ]);
            }
            $contract->update(['current_program_level_id' => $target->id]);

            return $exam;
        });
    }
}
