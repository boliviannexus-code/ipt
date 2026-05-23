<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Support\CompanyContext;

class StoreCashRegisterExpenseRequest extends FormRequest
{
    protected $errorBag = 'cashExpense';

    public function authorize(): bool
    {
        return ($this->user()?->can('pos.access') ?? false) && CompanyContext::canOperate($this->user());
    }

    public function rules(): array
    {
        return [
            'responsible_name' => ['required', 'string', 'max:255'],
            'detail' => ['required', 'string', 'max:1000'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function attributes(): array
    {
        return [
            'responsible_name' => 'encargado',
            'detail' => 'detalle',
            'amount' => 'monto',
        ];
    }
}
