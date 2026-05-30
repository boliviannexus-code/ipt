<?php

namespace App\Models;

use App\Models\Concerns\GlobalCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BedType extends Model
{
    use GlobalCatalog;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'capacity',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function roomBeds(): HasMany
    {
        return $this->hasMany(RoomBed::class);
    }
}
