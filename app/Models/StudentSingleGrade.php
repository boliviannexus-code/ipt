<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StudentSingleGrade extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'academic_module_id', 'program_grading_skill_id', 'student_id', 'score', 'graded_by', 'graded_at'];

    protected function casts(): array
    {
        return ['score' => 'decimal:2', 'graded_at' => 'immutable_datetime'];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(AcademicModule::class, 'academic_module_id');
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(ProgramGradingSkill::class, 'program_grading_skill_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
