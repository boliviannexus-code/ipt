<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicModuleStudentAssignment extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'academic_module_id', 'student_id', 'assigned_by', 'assigned_at'];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime'];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(AcademicModule::class, 'academic_module_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
