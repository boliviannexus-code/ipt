<?php

namespace Tests\Unit\Services;

use App\Services\Images\ImageStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageStorageServiceTest extends TestCase
{
    public function test_it_resizes_and_stores_uploaded_images_as_webp(): void
    {
        Storage::fake('public');

        $path = app(ImageStorageService::class)->store(
            UploadedFile::fake()->image('large-photo.png', 2400, 1200),
            'testing/images',
            1200,
            1200,
        );

        Storage::disk('public')->assertExists($path);
        $this->assertStringEndsWith('.webp', $path);

        $image = imagecreatefromstring(Storage::disk('public')->get($path));

        $this->assertNotFalse($image);
        $this->assertSame(1200, imagesx($image));
        $this->assertSame(600, imagesy($image));
        $this->assertSame('image/webp', Storage::disk('public')->mimeType($path));

        imagedestroy($image);
    }

    public function test_it_never_enlarges_a_small_image(): void
    {
        Storage::fake('public');

        $path = app(ImageStorageService::class)->store(
            UploadedFile::fake()->image('small-photo.jpg', 320, 180),
            'testing/images',
        );
        $image = imagecreatefromstring(Storage::disk('public')->get($path));

        $this->assertNotFalse($image);
        $this->assertSame(320, imagesx($image));
        $this->assertSame(180, imagesy($image));

        imagedestroy($image);
    }
}
