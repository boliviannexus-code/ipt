<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Enums\GradingSkillMode;
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
            $scheme->update(['status' => 'finalized', 'is_active' => true, 'finalized_by' => $user->id, 'finalized_at' => now()]);

            return $scheme->refresh();
        });
    }

    public function createIndependent(string $name, int $companyId, ?ProgramGradingScheme $source = null): ProgramGradingScheme
    {
        if ($source !== null) {
            abort_unless((int) $source->company_id === $companyId && $source->isFinalized(), 404);
            $source->loadMissing('components.skills');
        }

        return DB::transaction(function () use ($name, $companyId, $source): ProgramGradingScheme {
            $draft = ProgramGradingScheme::query()->create([
                'company_id' => $companyId,
                'name' => trim($name),
                'version' => $source ? $source->version + 1 : 1,
                'passing_score' => $source?->passing_score ?? 51,
                'status' => 'draft',
                'is_active' => false,
            ]);
            foreach ($source?->components ?? [] as $sourceComponent) {
                $component = $draft->components()->create([
                    'company_id' => $companyId,
                    'name' => $sourceComponent->name,
                    'weight' => $sourceComponent->weight,
                    'frequency' => $sourceComponent->frequency,
                    'skill_mode' => $sourceComponent->skill_mode,
                    'scoring_method' => $sourceComponent->scoring_method,
                    'position' => $sourceComponent->position,
                ]);
                $component->skills()->createMany($sourceComponent->skills->map(fn ($skill): array => [
                    'company_id' => $companyId,
                    'name' => $skill->name,
                    'weight' => $skill->weight,
                    'position' => $skill->position,
                ])->all());
            }

            return $draft->load('components.skills');
        });
    }

    public function rename(ProgramGradingScheme $scheme, string $name): void
    {
        $scheme->update(['name' => trim($name)]);
    }

    public function delete(ProgramGradingScheme $scheme): void
    {
        DB::transaction(function () use ($scheme): void {
            $scheme = ProgramGradingScheme::query()->lockForUpdate()->findOrFail($scheme->id);
            $scheme->loadExists(['levels', 'classSessions', 'studentResults']);
            if (! $scheme->canBeDeleted()) {
                throw ValidationException::withMessages([
                    'configuration' => 'No puedes eliminar esta ponderación porque está vinculada a un programa o tiene registros académicos asociados.',
                ]);
            }
            $scheme->delete();
        });
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
