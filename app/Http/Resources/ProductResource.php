<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'barcode' => $this->barcode,
            'category_id' => $this->category_id,
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'measurement_unit_id' => $this->measurement_unit_id,
            'measurement_unit' => $this->whenLoaded('measurementUnit', fn () => [
                'id' => $this->measurementUnit->id,
                'name' => $this->measurementUnit->name,
                'abbreviation' => $this->measurementUnit->abbreviation,
            ]),
            'description' => $this->description,
            'image_url' => $this->image_url,
            'purchase_price' => $this->purchase_price,
            'sale_price' => $this->sale_price,
            'minimum_stock' => $this->minimum_stock,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
