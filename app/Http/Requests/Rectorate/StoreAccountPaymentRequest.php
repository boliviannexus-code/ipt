<?php

namespace App\Http\Requests\Rectorate;

use App\Support\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('accounts.collect') ?? false;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0'],
            'payment_method_code' => [
                'required',
                'integer',
                Rule::exists('sin_catalog_items', 'classifier_code')
                    ->where('company_id', CompanyContext::id($this->user()))
                    ->where('catalog_key', 'tipos_metodo_pago')
                    ->where('is_active', true),
            ],
            'reference' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method_code.required' => 'Selecciona un método de pago.',
            'payment_method_code.exists' => 'Selecciona un método de pago vigente del catálogo del SIN.',
        ];
    }
}
