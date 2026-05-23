<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\CompanyContext;

class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('warehouses.update') ?? false) && CompanyContext::canOperate($this->user());
    }

    public function rules(): array
    {
        $warehouseId = $this->route('warehouse')?->id ?? $this->route('warehouse');
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
            'code' => ['required', 'string', 'max:50', Rule::unique('warehouses', 'code')->ignore($warehouseId)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
