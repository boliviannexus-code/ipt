<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StudentDailyGrade extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'class_session_grading_skill_id', 'student_id', 'score', 'graded_by', 'graded_at'];

    protected function casts(): array
    {
        return ['score' => 'decimal:2', 'graded_at' => 'immutable_datetime'];
    }

    public function sessionSkill(): BelongsTo
    {
        return $this->belongsTo(ClassSessionGradingSkill::class, 'class_session_grading_skill_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
