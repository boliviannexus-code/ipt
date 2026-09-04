<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'campus_id', 'account_number', 'identity_document', 'first_name', 'paternal_surname', 'maternal_surname',
        'birth_date', 'email', 'phone', 'gender', 'is_active',
    ];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'is_active' => 'boolean'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(RectorateApplication::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(EnrollmentContract::class);
    }

    public function moduleAssignments(): HasMany
    {
        return $this->hasMany(AcademicModuleStudentAssignment::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }

    public function moduleResults(): HasMany
    {
        return $this->hasMany(AcademicModuleStudentResult::class);
    }

    public function singleGrades(): HasMany
    {
        return $this->hasMany(StudentSingleGrade::class);
    }
}
