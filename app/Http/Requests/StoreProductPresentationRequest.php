<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\CompanyContext;

class StoreProductPresentationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('product-presentations.create') ?? false) && CompanyContext::canOperate($this->user());
    }

    public function rules(): array
    {
        $companyId = CompanyContext::id($this->user());

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('presentations', 'name')->where(fn ($query) => $query->where('company_id', $companyId))],
            'units_per_package' => ['required', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
