<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommercialOrigin extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = ['company_id', 'name'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
