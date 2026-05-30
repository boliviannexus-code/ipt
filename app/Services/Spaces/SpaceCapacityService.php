<?php

namespace App\Services\Spaces;

use App\Models\Space;
use App\Models\SpaceRoom;

class SpaceCapacityService
{
    public function hasValidPrivateDistribution(Space $space): bool
    {
        return (int) $space->max_capacity >= 1
            && (int) $space->bedrooms_count >= 0
            && (int) $space->beds_count >= 1
            && (int) $space->private_bathrooms_count >= 0
            && (int) $space->shared_bathrooms_count >= 0
            && $space->rooms()->count() === 0;
    }

    public function recalculateRoomCapacity(SpaceRoom $room): SpaceRoom
    {
        $room->update([
            'max_capacity' => (int) $room->beds()->sum('total_capacity'),
        ]);

        return $room;
    }

    public function recalculateSharedSpaceCapacity(Space $space): Space
    {
        $space->update([
            'max_capacity' => (int) $space->rooms()->where('status', 'active')->sum('max_capacity'),
            'bedrooms_count' => (int) $space->rooms()->count(),
            'beds_count' => (int) $space->rooms()->withSum('beds', 'quantity')->get()->sum('beds_sum_quantity'),
        ]);

        return $space;
    }

    public function hasValidSharedDistribution(Space $space): bool
    {
        return $space->rooms()->count() > 0
            && ! $space->rooms()->whereDoesntHave('beds')->exists();
    }
}
