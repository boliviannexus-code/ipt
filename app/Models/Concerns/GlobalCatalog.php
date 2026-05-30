<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

trait GlobalCatalog
{
    use SoftDeletes;

    public function initializeGlobalCatalog(): void
    {
        $this->mergeFillable([
            'name',
            'slug',
            'description',
            'is_active',
            'sort_order',
        ]);

        $this->mergeCasts([
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeIncludingInactive(Builder $query): Builder
    {
        return $query;
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByRaw('sort_order is null')->orderBy('sort_order')->orderBy('name');
    }
}
