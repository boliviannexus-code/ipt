<?php

namespace App\Models;

use App\Models\Concerns\GlobalCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RoomService extends Model
{
    use GlobalCatalog;

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(SpaceRoom::class, 'room_room_service')
            ->withPivot(['id', 'company_id'])
            ->withTimestamps();
    }
}
