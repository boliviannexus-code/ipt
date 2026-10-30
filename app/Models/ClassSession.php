<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassSession extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'academic_module_id', 'program_grading_scheme_id', 'personnel_id', 'started_by', 'class_date', 'started_at', 'ended_at', 'finalized_by'];

    protected function casts(): array
    {
        return ['class_date' => 'date', 'started_at' => 'datetime', 'ended_at' => 'datetime'];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(AcademicModule::class, 'academic_module_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'personnel_id');
    }

    public function gradingScheme(): BelongsTo
    {
        return $this->belongsTo(ProgramGradingScheme::class, 'program_grading_scheme_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }

    public function gradingSkills(): HasMany
    {
        return $this->hasMany(ClassSessionGradingSkill::class);
    }

    public function academicObservations(): HasMany
    {
        return $this->hasMany(StudentAcademicObservation::class);
    }
}
