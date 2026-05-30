<?php

namespace App\Http\Requests\Spaces;

use App\Models\PrivateSpaceType;
use App\Models\Space;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePrivateSpaceDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('spaces.create') === true
            && $this->route('space') instanceof Space
            && (int) $this->route('space')->company_id === (int) $this->user()->company_id;
    }

    public function rules(): array
    {
        $space = $this->route('space');

        return [
            'private_space_type_id' => [
                'required',
                Rule::exists((new PrivateSpaceType)->getTable(), 'id')->where('is_active', true),
            ],
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new Space)->getTable(), 'title')
                    ->where('company_id', $this->user()->company_id)
                    ->ignore($space->id),
            ],
            'max_capacity' => ['required', 'integer', 'min:1'],
            'bedrooms_count' => ['required', 'integer', 'min:0'],
            'beds_count' => ['required', 'integer', 'min:1'],
            'private_bathrooms_count' => ['required', 'integer', 'min:0'],
            'shared_bathrooms_count' => ['required', 'integer', 'min:0'],
        ];
    }
}
