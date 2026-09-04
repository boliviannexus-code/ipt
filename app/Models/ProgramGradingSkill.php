<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProgramGradingSkill extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'program_grading_component_id', 'name', 'weight', 'position'];

    protected function casts(): array
    {
        return ['weight' => 'decimal:2', 'position' => 'integer'];
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(ProgramGradingComponent::class, 'program_grading_component_id');
    }

    public function sessionSelections(): HasMany
    {
        return $this->hasMany(ClassSessionGradingSkill::class);
    }

    public function singleGrades(): HasMany
    {
        return $this->hasMany(StudentSingleGrade::class);
    }
}
