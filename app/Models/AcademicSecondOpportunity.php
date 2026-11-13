<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicSecondOpportunity extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'academic_module_student_result_id', 'enrollment_contract_id', 'original_score', 'original_status', 'score', 'status', 'passing_score', 'reason', 'recorded_by', 'recorded_at'];

    protected function casts(): array
    {
        return ['original_score' => 'decimal:2', 'score' => 'decimal:2', 'passing_score' => 'decimal:2', 'recorded_at' => 'immutable_datetime'];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(AcademicModuleStudentResult::class, 'academic_module_student_result_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
