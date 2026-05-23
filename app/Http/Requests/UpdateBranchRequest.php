<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\CompanyContext;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('branches.update') ?? false) && CompanyContext::canOperate($this->user());
    }

    public function rules(): array
    {
        $branchId = $this->route('branch')?->id ?? $this->route('branch');

        return [
            'name' => ['required', 'string', 'max:255'],
            'company_id' => [
                'nullable',
                Rule::exists('companies', 'id')->where('is_active', true),
            ],
            'code' => ['required', 'string', 'max:50', Rule::unique('branches', 'code')->ignore($branchId)],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
