<?php

namespace App\Models;

use App\Enums\InventoryMovementType;
use App\Models\Concerns\AuditsCompanyChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class InventoryMovement extends Model implements Auditable
{
    use AuditsCompanyChanges;

    protected $fillable = [
        'product_id',
        'presentation_id',
        'presentation_name',
        'warehouse_id',
        'user_id',
        'type',
        'quantity',
        'package_quantity',
        'units_per_package',
        'reference_id',
        'reference_type',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => InventoryMovementType::class,
            'quantity' => 'integer',
            'package_quantity' => 'integer',
            'units_per_package' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function presentation(): BelongsTo
    {
        return $this->belongsTo(Presentation::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
