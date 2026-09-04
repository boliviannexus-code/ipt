<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GradingFrequency;
use App\Enums\GradingScoringMethod;
use App\Enums\GradingSkillMode;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProgramGradingComponent extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'program_grading_scheme_id', 'name', 'weight', 'frequency', 'skill_mode', 'scoring_method', 'position'];

    protected function casts(): array
    {
        return ['weight' => 'decimal:2', 'frequency' => GradingFrequency::class, 'skill_mode' => GradingSkillMode::class, 'scoring_method' => GradingScoringMethod::class, 'position' => 'integer'];
    }

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(ProgramGradingScheme::class, 'program_grading_scheme_id');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(ProgramGradingSkill::class)->orderBy('position');
    }
}
