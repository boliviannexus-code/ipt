<?php

namespace App\Http\Requests\Spaces;

use App\Models\Space;
use Illuminate\Foundation\Http\FormRequest;

class StoreSpaceLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('spaces.create') === true
            && $this->route('space') instanceof Space
            && (int) $this->route('space')->company_id === (int) $this->user()->company_id;
    }

    public function rules(): array
    {
        return [
            'country' => ['required', 'string', 'max:120'],
            'state_or_region' => ['nullable', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'zone_or_neighborhood' => ['nullable', 'string', 'max:160'],
            'address' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
