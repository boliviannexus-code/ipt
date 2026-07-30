<?php

namespace App\Services\Spaces;

use App\Models\RoomPhoto;
use App\Models\Space;
use App\Models\SpacePhoto;
use App\Models\SpaceRoom;
use App\Services\Images\ImageStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SpacePhotoService
{
    public function __construct(
        private readonly ImageStorageService $images,
    ) {}

    public function storeMainPhoto(Space $space, UploadedFile $photo): SpacePhoto
    {
        $previousPhotos = $space->photos()->where('type', 'main')->get();
        $storedPhoto = $this->storePhoto($space, $photo, 'main', 0);

        $previousPhotos->each(fn (SpacePhoto $photo) => $this->deleteSpacePhoto($photo));

        return $storedPhoto;
    }

    public function storeGalleryPhotos(Space $space, array $photos): void
    {
        $nextOrder = (int) $space->photos()->where('type', 'gallery')->max('sort_order') + 1;

        foreach ($photos as $photo) {
            if ($photo instanceof UploadedFile) {
                $this->storePhoto($space, $photo, 'gallery', $nextOrder++);
            }
        }
    }

    public function storeRoomMainPhoto(SpaceRoom $room, UploadedFile $photo): RoomPhoto
    {
        $previousPhotos = $room->photos()->where('type', 'main')->get();
        $storedPhoto = $this->storeRoomPhoto($room, $photo, 'main', 0);

        $previousPhotos->each(fn (RoomPhoto $photo) => $this->deleteRoomPhoto($photo));

        return $storedPhoto;
    }

    public function storeRoomGalleryPhotos(SpaceRoom $room, array $photos): void
    {
        $nextOrder = (int) $room->photos()->where('type', 'gallery')->max('sort_order') + 1;

        foreach ($photos as $photo) {
            if ($photo instanceof UploadedFile) {
                $this->storeRoomPhoto($room, $photo, 'gallery', $nextOrder++);
            }
        }
    }

    public function deleteSpacePhoto(SpacePhoto $photo): void
    {
        Storage::disk('public')->delete($photo->path);
        $photo->delete();
    }

    public function deleteRoomPhoto(RoomPhoto $photo): void
    {
        Storage::disk('public')->delete($photo->path);
        $photo->delete();
    }

    private function storePhoto(Space $space, UploadedFile $photo, string $type, int $sortOrder): SpacePhoto
    {
        $path = $this->storeAsWebp($space, $photo);

        return $space->photos()->create([
            'company_id' => $space->company_id,
            'path' => $path,
            'type' => $type,
            'sort_order' => $sortOrder,
            'alt_text' => $space->title,
        ]);
    }

    private function storeAsWebp(Space $space, UploadedFile $photo): string
    {
        $directory = "accommodations/company-{$space->company_id}/spaces/{$space->id}";

        return $this->images->store($photo, $directory);
    }

    private function storeRoomPhoto(SpaceRoom $room, UploadedFile $photo, string $type, int $sortOrder): RoomPhoto
    {
        $path = $this->storeRoomAsWebp($room, $photo);

        return $room->photos()->create([
            'company_id' => $room->company_id,
            'path' => $path,
            'type' => $type,
            'sort_order' => $sortOrder,
            'alt_text' => $room->title,
        ]);
    }

    private function storeRoomAsWebp(SpaceRoom $room, UploadedFile $photo): string
    {
        $directory = "accommodations/company-{$room->company_id}/spaces/{$room->space_id}/rooms/{$room->id}";

        return $this->images->store($photo, $directory);
    }
}
