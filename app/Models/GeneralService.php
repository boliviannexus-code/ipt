<?php

namespace App\Models;

use App\Models\Concerns\GlobalCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GeneralService extends Model
{
    use GlobalCatalog;

    public function spaces(): BelongsToMany
    {
        return $this->belongsToMany(Space::class, 'space_general_service')
            ->withPivot(['id', 'company_id'])
            ->withTimestamps();
    }
}
