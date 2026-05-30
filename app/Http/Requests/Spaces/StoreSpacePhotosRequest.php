<?php

namespace App\Http\Requests\Spaces;

use App\Models\Space;
use Illuminate\Foundation\Http\FormRequest;

class StoreSpacePhotosRequest extends FormRequest
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
            'main_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'gallery_photos' => ['nullable', 'array', 'max:5'],
            'gallery_photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'photos_skipped' => ['nullable', 'boolean'],
        ];
    }
}
