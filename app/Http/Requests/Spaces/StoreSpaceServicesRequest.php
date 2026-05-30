<?php

namespace App\Http\Requests\Spaces;

use App\Models\GeneralService;
use App\Models\Space;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSpaceServicesRequest extends FormRequest
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
            'general_services' => ['nullable', 'array'],
            'general_services.*' => [
                'integer',
                Rule::exists((new GeneralService)->getTable(), 'id')->where('is_active', true),
            ],
        ];
    }
}
