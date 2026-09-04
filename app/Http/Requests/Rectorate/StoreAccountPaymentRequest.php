<?php

namespace App\Http\Requests\Rectorate;

use App\Enums\AccountPaymentMethod;
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
                Rule::in(AccountPaymentMethod::values()),
            ],
            'reference' => [
                Rule::requiredIf(fn (): bool => AccountPaymentMethod::tryFrom((int) $this->input('payment_method_code'))?->requiresReference() ?? false),
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method_code.required' => 'Selecciona un método de pago.',
            'payment_method_code.in' => 'Selecciona Efectivo, QR o Transferencia.',
            'reference.required' => 'Ingresa la referencia del pago.',
        ];
    }
}
