<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ClassSessionGradingSkill extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'class_session_id', 'program_grading_skill_id', 'selected_by', 'selected_at'];

    protected function casts(): array
    {
        return ['selected_at' => 'immutable_datetime'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(ProgramGradingSkill::class, 'program_grading_skill_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(StudentDailyGrade::class);
    }
}
