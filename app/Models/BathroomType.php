<?php

namespace App\Models;

use App\Models\Concerns\GlobalCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BathroomType extends Model
{
    use GlobalCatalog;

    public function rooms(): HasMany
    {
        return $this->hasMany(SpaceRoom::class);
    }
}
