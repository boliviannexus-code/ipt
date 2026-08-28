<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campus extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = ['company_id', 'name', 'code', 'address'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function personnel(): HasMany
    {
        return $this->hasMany(Personnel::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(RectorateApplication::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
