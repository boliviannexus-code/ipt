<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\ProgramGradingScheme;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class AttendanceGradingPolicy
{
    public static function skills(?ProgramGradingScheme $scheme): Collection
    {
        if (! $scheme) {
            return collect();
        }
        $scheme->loadMissing('components.skills');

        return $scheme->components->filter(fn ($component): bool => mb_strtolower(trim($component->name)) === 'attendance'
            && $component->frequency->value === 'daily')
            ->flatMap(fn ($component) => $component->skills)->values();
    }

    public static function component(float $weight = 10): array
    {
        return [
            'name' => 'Attendance', 'weight' => $weight, 'frequency' => 'daily',
            'skill_mode' => 'single_skill', 'scoring_method' => 'simple',
            'skills' => [['name' => 'Attendance', 'weight' => 100]],
        ];
    }

    public static function validate(array $components): void
    {
        $attendance = collect($components)->filter(fn ($item): bool => is_array($item)
            && mb_strtolower(trim((string) ($item['name'] ?? ''))) === 'attendance');
        if ($attendance->count() !== 1) {
            throw ValidationException::withMessages(['components' => 'Debe existir exactamente un ítem Attendance. No se puede eliminar ni cambiar su nombre.']);
        }
        $item = $attendance->first();
        $skills = array_values($item['skills'] ?? []);
        if (($item['name'] ?? '') !== 'Attendance' || ($item['frequency'] ?? '') !== 'daily'
            || ($item['skill_mode'] ?? '') !== 'single_skill' || ($item['scoring_method'] ?? '') !== 'simple'
            || count($skills) !== 1 || ($skills[0]['name'] ?? '') !== 'Attendance'
            || (float) ($skills[0]['weight'] ?? 0) !== 100.0
            || (float) ($item['weight'] ?? 0) <= 0 || (float) $item['weight'] > 100) {
            throw ValidationException::withMessages(['components' => 'Attendance debe ser diaria, de método simple y una habilidad Attendance al 100%. Solo puede cambiar su porcentaje general.']);
        }
    }

    public static function ensureDraft(ProgramGradingScheme $scheme): void
    {
        if ($scheme->isFinalized()) {
            return;
        }
        $component = $scheme->components()->whereRaw('LOWER(TRIM(name)) = ?', ['attendance'])->first();
        $data = self::component($component ? (float) $component->weight : 10);
        $skills = $data['skills'];
        unset($data['skills']);
        if ($component) {
            $component->update($data);
        } else {
            $component = $scheme->components()->create([...$data, 'company_id' => $scheme->company_id, 'position' => ($scheme->components()->max('position') ?? 0) + 1]);
        }
        $existingSkills = $component->skills()->get();
        if ($existingSkills->count() !== 1 || $existingSkills->first()->name !== 'Attendance'
            || (float) $existingSkills->first()->weight !== 100.0) {
            $component->skills()->delete();
            $component->skills()->create([...$skills[0], 'company_id' => $scheme->company_id, 'position' => 1]);
        }
        $scheme->unsetRelation('components');
    }
}
