<?php

namespace App\Services\Spaces;

use App\Models\RoomPhoto;
use App\Models\Space;
use App\Models\SpacePhoto;
use App\Models\SpaceRoom;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class SpacePhotoService
{
    public function storeMainPhoto(Space $space, UploadedFile $photo): SpacePhoto
    {
        $space->photos()->where('type', 'main')->get()->each(function (SpacePhoto $photo): void {
            Storage::disk('public')->delete($photo->path);
            $photo->delete();
        });

        return $this->storePhoto($space, $photo, 'main', 0);
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
        $room->photos()->where('type', 'main')->get()->each(function (RoomPhoto $photo): void {
            Storage::disk('public')->delete($photo->path);
            $photo->delete();
        });

        return $this->storeRoomPhoto($room, $photo, 'main', 0);
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
        $image = match (strtolower($photo->getClientOriginalExtension())) {
            'jpg', 'jpeg' => imagecreatefromjpeg($photo->getRealPath()),
            'png' => imagecreatefrompng($photo->getRealPath()),
            'webp' => imagecreatefromwebp($photo->getRealPath()),
            default => false,
        };

        if ($image === false) {
            throw new RuntimeException('No se pudo procesar la imagen.');
        }

        if (function_exists('imagepalettetotruecolor')) {
            imagepalettetotruecolor($image);
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        $directory = "accommodations/company-{$space->company_id}/spaces/{$space->id}";
        $filename = Str::uuid()->toString().'.webp';
        $temporaryPath = tempnam(sys_get_temp_dir(), 'space-photo-');

        $stored = imagewebp($image, $temporaryPath, 82);
        imagedestroy($image);

        if (! $stored) {
            @unlink($temporaryPath);

            throw new RuntimeException('No se pudo guardar la imagen optimizada.');
        }

        $path = "{$directory}/{$filename}";
        Storage::disk('public')->put($path, file_get_contents($temporaryPath));
        @unlink($temporaryPath);

        return $path;
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
        $image = match (strtolower($photo->getClientOriginalExtension())) {
            'jpg', 'jpeg' => imagecreatefromjpeg($photo->getRealPath()),
            'png' => imagecreatefrompng($photo->getRealPath()),
            'webp' => imagecreatefromwebp($photo->getRealPath()),
            default => false,
        };

        if ($image === false) {
            throw new RuntimeException('No se pudo procesar la imagen.');
        }

        if (function_exists('imagepalettetotruecolor')) {
            imagepalettetotruecolor($image);
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        $directory = "accommodations/company-{$room->company_id}/spaces/{$room->space_id}/rooms/{$room->id}";
        $filename = Str::uuid()->toString().'.webp';
        $temporaryPath = tempnam(sys_get_temp_dir(), 'room-photo-');
        $stored = imagewebp($image, $temporaryPath, 82);
        imagedestroy($image);

        if (! $stored) {
            @unlink($temporaryPath);

            throw new RuntimeException('No se pudo guardar la imagen optimizada.');
        }

        $path = "{$directory}/{$filename}";
        Storage::disk('public')->put($path, file_get_contents($temporaryPath));
        @unlink($temporaryPath);

        return $path;
    }
}
