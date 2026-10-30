<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountCharge extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'enrollment_contract_id', 'plan_id', 'concept', 'period', 'due_date', 'amount', 'paid_amount', 'status', 'charge_type', 'additional_service_id', 'detail', 'created_by', 'submission_token', 'placement_exam_id'];

    protected function casts(): array
    {
        return ['period' => 'date', 'due_date' => 'date', 'amount' => 'decimal:2', 'paid_amount' => 'decimal:2'];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(EnrollmentContract::class, 'enrollment_contract_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }
}
