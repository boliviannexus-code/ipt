<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnrollmentContract extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id', 'campus_id', 'account_number', 'rectorate_application_id', 'student_id', 'program_id', 'plan_id',
        'contract_number', 'monthly_amount', 'status', 'confirmed_at', 'enrolled_at',
    ];

    protected function casts(): array
    {
        return [
            'contract_number' => 'integer',
            'monthly_amount' => 'decimal:2',
            'confirmed_at' => 'immutable_datetime',
            'enrolled_at' => 'immutable_datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(RectorateApplication::class, 'rectorate_application_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class)->withTrashed();
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(AccountCharge::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(AccountPayment::class);
    }

    public function planHistory(): HasMany
    {
        return $this->hasMany(ContractPlanHistory::class);
    }
}
