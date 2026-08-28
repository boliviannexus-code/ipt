<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractPlanHistory extends Model
{
    public $timestamps = false;

    protected $fillable = ['enrollment_contract_id', 'plan_id', 'monthly_amount', 'effective_from', 'created_at'];

    protected function casts(): array
    {
        return ['monthly_amount' => 'decimal:2', 'effective_from' => 'date', 'created_at' => 'immutable_datetime'];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(EnrollmentContract::class, 'enrollment_contract_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
