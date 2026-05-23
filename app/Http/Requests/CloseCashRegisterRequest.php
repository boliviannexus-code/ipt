<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Support\CompanyContext;

class CloseCashRegisterRequest extends FormRequest
{
    protected $errorBag = 'cashClose';

    public function authorize(): bool
    {
        return ($this->user()?->can('pos.access') ?? false) && CompanyContext::canOperate($this->user());
    }

    public function rules(): array
    {
        return [
            'closing_amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'closing_amount' => 'efectivo contado',
        ];
    }
}
