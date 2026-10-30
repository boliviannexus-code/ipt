<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class AdditionalService extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'title', 'detail', 'cost', 'is_active', 'automatic_trigger'];

    protected function casts(): array
    {
        return ['cost' => 'decimal:2', 'is_active' => 'boolean'];
    }
}
