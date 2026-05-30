<?php

namespace App\Models;

use App\Models\Concerns\GlobalCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrivateSpaceType extends Model
{
    use GlobalCatalog;

    public function spaces(): HasMany
    {
        return $this->hasMany(Space::class);
    }
}
