<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = ['company_id', 'name', 'monthly_cost'];

    protected function casts(): array
    {
        return ['monthly_cost' => 'decimal:2'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class)->withTimestamps();
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(EnrollmentContract::class);
    }
}
