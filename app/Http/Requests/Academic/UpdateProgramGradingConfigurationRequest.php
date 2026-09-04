<?php

declare(strict_types=1);

namespace App\Http\Requests\Academic;

use App\Enums\GradingFrequency;
use App\Enums\GradingScoringMethod;
use App\Enums\GradingSkillMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateProgramGradingConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('academic-control.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'passing_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'components' => ['present', 'array'],
            'components.*.name' => ['required', 'string', 'max:100'],
            'components.*.weight' => ['required', 'numeric', 'gt:0', 'max:100'],
            'components.*.frequency' => ['required', Rule::enum(GradingFrequency::class)],
            'components.*.skill_mode' => ['required', Rule::enum(GradingSkillMode::class)],
            'components.*.scoring_method' => ['required', Rule::enum(GradingScoringMethod::class)],
            'components.*.skills' => ['required', 'array', 'min:1', 'max:10'],
            'components.*.skills.*.name' => ['required', 'string', 'max:100'],
            'components.*.skills.*.weight' => ['required', 'numeric', 'gt:0', 'max:100'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $components = collect($this->input('components', []));
            $names = $components->pluck('name')->map(fn ($name): string => mb_strtolower(trim((string) $name)));
            if ($names->duplicates()->isNotEmpty()) {
                $validator->errors()->add('components', 'Los títulos de las ponderaciones no pueden repetirse.');
            }

            foreach ($components as $index => $component) {
                $skills = collect($component['skills'] ?? []);
                $mode = $component['skill_mode'] ?? null;
                if ($mode === GradingSkillMode::Single->value && $skills->count() !== 1) {
                    $validator->errors()->add("components.{$index}.skills", 'La calificación única debe tener exactamente una habilidad.');
                }
                $skillNames = $skills->pluck('name')->map(fn ($name): string => mb_strtolower(trim((string) $name)));
                if ($skillNames->duplicates()->isNotEmpty()) {
                    $validator->errors()->add("components.{$index}.skills", 'Los nombres de las habilidades no pueden repetirse.');
                }
            }
        }];
    }
}
