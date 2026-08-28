<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAllocation extends Model
{
    public $timestamps = false;

    protected $fillable = ['account_payment_id', 'account_charge_id', 'amount', 'created_at'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'created_at' => 'immutable_datetime'];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(AccountPayment::class, 'account_payment_id');
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(AccountCharge::class, 'account_charge_id');
    }
}
