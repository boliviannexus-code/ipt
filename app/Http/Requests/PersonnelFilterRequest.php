<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PersonnelFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('personnel.view') ?? false;
    }

    public function rules(): array
    {
        return ['area_id' => ['nullable', 'integer', 'min:1'], 'position_id' => ['nullable', 'integer', 'min:1']];
    }
}
