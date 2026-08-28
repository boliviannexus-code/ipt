<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'company_id' => $this->company_id,
            'personnel' => $this->whenLoaded('personnel', fn (): array => [
                'id' => $this->personnel->id,
                'full_name' => $this->personnel->full_name,
                'identity_document' => $this->personnel->identity_document,
                'position' => $this->personnel->position?->name,
                'area' => $this->personnel->position?->area?->name,
            ]),
            'is_active' => $this->is_active,
            'roles' => $this->roles->pluck('name')->values(),
            'role_labels' => $this->roles->pluck('name')->map(fn (string $role): string => role_label($role))->values(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
