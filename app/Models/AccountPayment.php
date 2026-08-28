<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountPayment extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'enrollment_contract_id', 'cash_register_id', 'received_by', 'amount', 'payment_method_code', 'reference', 'paid_at'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'payment_method_code' => 'integer', 'paid_at' => 'immutable_datetime'];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(EnrollmentContract::class, 'enrollment_contract_id');
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }
}
