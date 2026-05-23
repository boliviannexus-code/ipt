<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\CompanyContext;

class OpenCashRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('pos.access') ?? false) && CompanyContext::canOperate($this->user());
    }

    public function rules(): array
    {
        return [
            'point_of_sale_id' => [
                'required',
                Rule::exists('point_of_sales', 'id')->where('is_active', true),
            ],
            'opening_amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'point_of_sale_id' => 'punto de venta',
            'opening_amount' => 'monto inicial',
        ];
    }
}
