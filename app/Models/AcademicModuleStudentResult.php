<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicModuleStudentResult extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'academic_module_id', 'student_id', 'status', 'account_charge_id', 'finalized_by', 'finalized_at'];

    protected function casts(): array
    {
        return ['finalized_at' => 'datetime'];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(AcademicModule::class, 'academic_module_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(AccountCharge::class, 'account_charge_id');
    }
}
