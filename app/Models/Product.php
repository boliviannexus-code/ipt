<?php

namespace App\Models;

use App\Models\Concerns\AuditsCompanyChanges;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use OwenIt\Auditing\Contracts\Auditable;

class Product extends Model implements HasMedia, Auditable
{
    public const IMAGE_COLLECTION = 'images';

    public const IMAGE_CONVERSION = 'optimized';

    /** @use HasFactory<ProductFactory> */
    use AuditsCompanyChanges, HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'name',
        'company_id',
        'barcode',
        'category_id',
        'measurement_unit_id',
        'description',
        'image_path',
        'purchase_price',
        'sale_price',
        'minimum_stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'minimum_stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function measurementUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        $mediaUrl = $this->getFirstMediaUrl(self::IMAGE_COLLECTION, self::IMAGE_CONVERSION);

        if ($mediaUrl !== '') {
            return $mediaUrl;
        }

        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection(self::IMAGE_COLLECTION)
            ->singleFile()
            ->useDisk('public')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this
            ->addMediaConversion(self::IMAGE_CONVERSION)
            ->performOnCollections(self::IMAGE_COLLECTION)
            ->fit(Fit::Crop, 600, 600)
            ->format('webp')
            ->nonQueued();
    }
}
