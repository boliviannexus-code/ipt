<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AcademicModule extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'program_id', 'program_level_id', 'name', 'modality', 'starts_at', 'ends_at', 'start_date', 'end_date', 'closed_at', 'closed_by'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'closed_at' => 'immutable_datetime'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(ProgramLevel::class, 'program_level_id');
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(AcademicModuleTeacherAssignment::class)->latest('assigned_at');
    }

    public function currentTeacherAssignment(): HasOne
    {
        return $this->hasOne(AcademicModuleTeacherAssignment::class)->whereNull('unassigned_at')->latestOfMany('assigned_at');
    }

    public function studentAssignments(): HasMany
    {
        return $this->hasMany(AcademicModuleStudentAssignment::class);
    }

    public function classSessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }

    public function studentResults(): HasMany
    {
        return $this->hasMany(AcademicModuleStudentResult::class);
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function academicObservations(): HasManyThrough
    {
        return $this->hasManyThrough(StudentAcademicObservation::class, ClassSession::class);
    }

    public function singleGrades(): HasMany
    {
        return $this->hasMany(StudentSingleGrade::class);
    }
}
