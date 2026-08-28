<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicModuleTeacherAssignment extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'academic_module_id', 'personnel_id', 'assigned_at', 'unassigned_at'];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime', 'unassigned_at' => 'datetime'];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(AcademicModule::class, 'academic_module_id');
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }
}
