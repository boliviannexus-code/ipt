<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\CompanyContext;

class UpdatePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('payment-methods.update') ?? false) && CompanyContext::canOperate($this->user());
    }

    public function rules(): array
    {
        $paymentMethodId = $this->route('payment_method')?->id ?? $this->route('payment_method');
        $companyId = CompanyContext::id($this->user());

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('payment_methods', 'name')->where(fn ($query) => $query->where('company_id', $companyId))->ignore($paymentMethodId)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
