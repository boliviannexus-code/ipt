<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProgramGradingScheme extends Model
{
    use BelongsToCompany;

    protected $fillable = ['name', 'company_id', 'program_id', 'version', 'passing_score', 'status', 'is_active', 'finalized_by', 'finalized_at'];

    protected function casts(): array
    {
        return ['version' => 'integer', 'passing_score' => 'decimal:2', 'is_active' => 'boolean', 'finalized_at' => 'immutable_datetime'];
    }

    public function components(): HasMany
    {
        return $this->hasMany(ProgramGradingComponent::class)->orderBy('position');
    }

    public function levels(): HasMany
    {
        return $this->hasMany(ProgramLevel::class);
    }

    public function classSessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }

    public function studentResults(): HasMany
    {
        return $this->hasMany(AcademicModuleStudentResult::class);
    }

    public function canBeDeleted(): bool
    {
        return $this->program_id === null && ! $this->levels_exists
            && ! $this->class_sessions_exists && ! $this->student_results_exists;
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function isFinalized(): bool
    {
        return $this->status === 'finalized';
    }
}
