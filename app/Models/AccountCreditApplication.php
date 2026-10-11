<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountCreditApplication extends Model
{
    use BelongsToCompany;

    public $timestamps = false;

    protected $fillable = ['company_id', 'enrollment_contract_id', 'cash_register_id', 'applied_by', 'amount', 'created_at'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'created_at' => 'immutable_datetime'];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(EnrollmentContract::class, 'enrollment_contract_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }
}
