<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\CompanyContext;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('suppliers.update') ?? false) && CompanyContext::canOperate($this->user());
    }

    public function rules(): array
    {
        $supplierId = $this->route('supplier')?->id ?? $this->route('supplier');
        $companyId = CompanyContext::id($this->user());

        return [
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('suppliers', 'email')->where(fn ($query) => $query->where('company_id', $companyId))->ignore($supplierId)],
            'phone' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
