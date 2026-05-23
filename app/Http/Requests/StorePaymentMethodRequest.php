<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\CompanyContext;

class StorePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('payment-methods.create') ?? false) && CompanyContext::canOperate($this->user());
    }

    public function rules(): array
    {
        $companyId = CompanyContext::id($this->user());

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('payment_methods', 'name')->where(fn ($query) => $query->where('company_id', $companyId))],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
