<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = ['company_id', 'area_id', 'name', 'is_academic', 'is_sales_executive', 'is_active'];

    protected function casts(): array
    {
        return ['is_academic' => 'boolean', 'is_sales_executive' => 'boolean', 'is_active' => 'boolean'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function personnel(): HasMany
    {
        return $this->hasMany(Personnel::class);
    }
}
