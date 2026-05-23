<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\CompanyContext;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('warehouses.create') ?? false) && CompanyContext::canOperate($this->user());
    }

    public function rules(): array
    {
        $branchRule = Rule::exists('branches', 'id')->whereNull('deleted_at');

        if ($companyId = CompanyContext::id($this->user())) {
            $branchRule->where('company_id', $companyId);
        }

        return [
            'branch_id' => [
                'required',
                $branchRule,
            ],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:warehouses,code'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
