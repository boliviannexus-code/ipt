<?php

namespace App\Support;

use App\Models\BathroomType;
use App\Models\BedType;
use App\Models\GeneralService;
use App\Models\PrivateSpaceType;
use App\Models\RoomService;
use App\Models\SharedSpaceType;
use App\Models\SpaceMode;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class AccommodationCatalogRegistry
{
    public const string PERMISSION = 'accommodation-catalogs.manage';

    public static function all(): array
    {
        return [
            'space-modes' => [
                'label' => 'Modalidades',
                'singular' => 'Modalidad',
                'model' => SpaceMode::class,
                'usage_relation' => 'spaces',
                'protected_slugs' => ['privado', 'compartido'],
                'has_capacity' => false,
            ],
            'private-space-types' => [
                'label' => 'Tipos privados',
                'singular' => 'Tipo privado',
                'model' => PrivateSpaceType::class,
                'usage_relation' => 'spaces',
                'protected_slugs' => [],
                'has_capacity' => false,
            ],
            'shared-space-types' => [
                'label' => 'Tipos compartidos',
                'singular' => 'Tipo compartido',
                'model' => SharedSpaceType::class,
                'usage_relation' => 'spaces',
                'protected_slugs' => [],
                'has_capacity' => false,
            ],
            'bed-types' => [
                'label' => 'Tipos de cama',
                'singular' => 'Tipo de cama',
                'model' => BedType::class,
                'usage_relation' => 'roomBeds',
                'protected_slugs' => [],
                'has_capacity' => true,
            ],
            'bathroom-types' => [
                'label' => 'Tipos de baño',
                'singular' => 'Tipo de baño',
                'model' => BathroomType::class,
                'usage_relation' => 'rooms',
                'protected_slugs' => [],
                'has_capacity' => false,
            ],
            'general-services' => [
                'label' => 'Servicios generales',
                'singular' => 'Servicio general',
                'model' => GeneralService::class,
                'usage_relation' => 'spaces',
                'protected_slugs' => [],
                'has_capacity' => false,
            ],
            'room-services' => [
                'label' => 'Servicios de habitación',
                'singular' => 'Servicio de habitación',
                'model' => RoomService::class,
                'usage_relation' => 'rooms',
                'protected_slugs' => [],
                'has_capacity' => false,
            ],
        ];
    }

    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function get(string $key): array
    {
        return self::all()[$key] ?? throw new InvalidArgumentException("Catalogo no soportado: {$key}");
    }

    public static function modelClass(string $key): string
    {
        return self::get($key)['model'];
    }

    public static function resolveRecord(string $key, int|string $id): Model
    {
        $modelClass = self::modelClass($key);

        return $modelClass::withTrashed()->findOrFail($id);
    }

    public static function isProtected(string $key, Model $record): bool
    {
        return in_array($record->getAttribute('slug'), self::get($key)['protected_slugs'], true);
    }
}
