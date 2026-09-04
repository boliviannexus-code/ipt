<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Enums\GradingSkillMode;
use App\Models\Program;
use App\Models\ProgramGradingScheme;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ProgramGradingConfigurationService
{
    public function update(ProgramGradingScheme $scheme, array $data): ProgramGradingScheme
    {
        $this->ensureEditable($scheme);

        return DB::transaction(function () use ($scheme, $data): ProgramGradingScheme {
            $scheme->update(['passing_score' => $data['passing_score']]);
            $scheme->components()->delete();

            foreach (array_values($data['components']) as $componentPosition => $componentData) {
                $component = $scheme->components()->create([
                    'company_id' => $scheme->company_id,
                    'name' => trim($componentData['name']),
                    'weight' => $componentData['weight'],
                    'frequency' => $componentData['frequency'],
                    'skill_mode' => $componentData['skill_mode'],
                    'scoring_method' => $componentData['scoring_method'],
                    'position' => $componentPosition + 1,
                ]);

                foreach (array_values($componentData['skills']) as $skillPosition => $skillData) {
                    $component->skills()->create([
                        'company_id' => $scheme->company_id,
                        'name' => trim($skillData['name']),
                        'weight' => $componentData['skill_mode'] === GradingSkillMode::Single->value ? 100 : $skillData['weight'],
                        'position' => $skillPosition + 1,
                    ]);
                }
            }

            return $scheme->refresh()->load('components.skills');
        });
    }

    public function finalize(ProgramGradingScheme $scheme, User $user): ProgramGradingScheme
    {
        $this->ensureEditable($scheme);
        $scheme->load('components.skills');
        $this->validateForFinalization($scheme);

        return DB::transaction(function () use ($scheme, $user): ProgramGradingScheme {
            ProgramGradingScheme::query()->where('program_id', $scheme->program_id)->where('is_active', true)->update(['is_active' => false]);
            $scheme->update(['status' => 'finalized', 'is_active' => true, 'finalized_by' => $user->id, 'finalized_at' => now()]);

            return $scheme->refresh();
        });
    }

    public function createNextVersion(Program $program, ?ProgramGradingScheme $source = null, bool $createEmpty = false): ProgramGradingScheme
    {
        if ($program->gradingSchemes()->where('status', 'draft')->exists()) {
            throw ValidationException::withMessages(['configuration' => 'El programa ya tiene una versión en borrador.']);
        }

        if (! $createEmpty) {
            $source ??= $program->gradingSchemes()->with('components.skills')->where('status', 'finalized')->first();
        }
        if ($source !== null) {
            abort_unless((int) $source->company_id === $program->company_id && $source->isFinalized(), 404);
            $source->loadMissing('components.skills');
        }
        $nextVersion = ((int) $program->gradingSchemes()->max('version')) + 1;

        return DB::transaction(function () use ($nextVersion, $program, $source): ProgramGradingScheme {
            $draft = $program->gradingSchemes()->create([
                'company_id' => $program->company_id,
                'version' => $nextVersion,
                'passing_score' => $source?->passing_score ?? 51,
                'status' => 'draft',
                'is_active' => false,
            ]);
            foreach ($source?->components ?? [] as $sourceComponent) {
                $component = $draft->components()->create([
                    'company_id' => $program->company_id,
                    'name' => $sourceComponent->name,
                    'weight' => $sourceComponent->weight,
                    'frequency' => $sourceComponent->frequency,
                    'skill_mode' => $sourceComponent->skill_mode,
                    'scoring_method' => $sourceComponent->scoring_method,
                    'position' => $sourceComponent->position,
                ]);
                $component->skills()->createMany($sourceComponent->skills->map(fn ($skill): array => [
                    'company_id' => $program->company_id,
                    'name' => $skill->name,
                    'weight' => $skill->weight,
                    'position' => $skill->position,
                ])->all());
            }

            return $draft->load('components.skills');
        });
    }

    public function deleteDraft(ProgramGradingScheme $scheme): void
    {
        $this->ensureEditable($scheme);
        $scheme->delete();
    }

    private function validateForFinalization(ProgramGradingScheme $scheme): void
    {
        if ($scheme->components->isEmpty()) {
            throw ValidationException::withMessages(['components' => 'Agrega al menos una ponderación antes de finalizar.']);
        }
        if (round((float) $scheme->components->sum('weight'), 2) !== 100.0) {
            throw ValidationException::withMessages(['components' => 'Las ponderaciones deben sumar exactamente 100%.']);
        }
        foreach ($scheme->components as $component) {
            $expectedSkills = $component->skill_mode === GradingSkillMode::Single ? 1 : null;
            $skillCount = $component->skills->count();
            if (($expectedSkills !== null && $skillCount !== $expectedSkills)
                || ($expectedSkills === null && ($skillCount < 2 || $skillCount > 10))
                || round((float) $component->skills->sum('weight'), 2) !== 100.0) {
                throw ValidationException::withMessages(['components' => "La ponderación {$component->name} tiene una configuración de habilidades incompleta."]);
            }
        }
    }

    private function ensureEditable(ProgramGradingScheme $scheme): void
    {
        if ($scheme->isFinalized()) {
            throw ValidationException::withMessages(['configuration' => 'Esta versión ya fue finalizada y no puede modificarse.']);
        }
    }
}
