<?php

namespace App\Services\Images;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImageStorageService
{
    public function store(
        UploadedFile $file,
        string $directory,
        ?int $maxWidth = null,
        ?int $maxHeight = null,
        ?string $disk = null,
    ): string {
        $dimensions = @getimagesize($file->getRealPath());

        if (
            $dimensions === false
            || ((int) $dimensions[0] * (int) $dimensions[1]) > (int) config('images.max_pixels', 40000000)
        ) {
            throw new RuntimeException('La imagen no es válida o sus dimensiones son demasiado grandes.');
        }

        $contents = file_get_contents($file->getRealPath());
        $source = $contents === false ? false : @imagecreatefromstring($contents);

        if ($source === false) {
            throw new RuntimeException('No se pudo procesar la imagen seleccionada.');
        }

        try {
            $source = $this->orient($source, $file);
            $sourceWidth = imagesx($source);
            $sourceHeight = imagesy($source);
            $limitWidth = max(1, $maxWidth ?? (int) config('images.max_width', 1920));
            $limitHeight = max(1, $maxHeight ?? (int) config('images.max_height', 1920));
            $scale = min(1, $limitWidth / $sourceWidth, $limitHeight / $sourceHeight);
            $width = max(1, (int) round($sourceWidth * $scale));
            $height = max(1, (int) round($sourceHeight * $scale));
            $output = imagecreatetruecolor($width, $height);

            if ($output === false) {
                throw new RuntimeException('No se pudo preparar la imagen optimizada.');
            }

            try {
                imagealphablending($output, false);
                imagesavealpha($output, true);
                $transparent = imagecolorallocatealpha($output, 0, 0, 0, 127);
                imagefilledrectangle($output, 0, 0, $width, $height, $transparent);

                if (! imagecopyresampled(
                    $output,
                    $source,
                    0,
                    0,
                    0,
                    0,
                    $width,
                    $height,
                    $sourceWidth,
                    $sourceHeight,
                )) {
                    throw new RuntimeException('No se pudo redimensionar la imagen.');
                }

                ob_start();
                $encoded = imagewebp($output, null, (int) config('images.quality', 82));
                $webp = ob_get_clean();

                if (! $encoded || ! is_string($webp) || $webp === '') {
                    throw new RuntimeException('No se pudo convertir la imagen a WebP.');
                }
            } finally {
                imagedestroy($output);
            }
        } finally {
            imagedestroy($source);
        }

        $path = trim($directory, '/').'/'.Str::uuid().'.webp';
        $stored = Storage::disk($disk ?? config('images.disk', 'public'))->put($path, $webp);

        if (! $stored) {
            throw new RuntimeException('No se pudo guardar la imagen optimizada.');
        }

        return $path;
    }

    private function orient(\GdImage $image, UploadedFile $file): \GdImage
    {
        if (! function_exists('exif_read_data') || $file->getMimeType() !== 'image/jpeg') {
            return $image;
        }

        $exif = @exif_read_data($file->getRealPath());
        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);

        if ($rotated === false) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }
}
