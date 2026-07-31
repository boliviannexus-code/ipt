<?php

namespace App\Http\Requests;

use App\Models\SinBranch;
use App\Support\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSinPointOfSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $branch = $this->route('branch');

        return $this->user()?->company_id !== null
            && ($this->user()?->can('siat-branches.manage') ?? false)
            && $branch instanceof SinBranch
            && CompanyContext::belongsToUser($branch->company_id, $this->user());
    }

    public function rules(): array
    {
        $branch = $this->route('branch');
        $companyId = CompanyContext::id($this->user());

        return [
            'point_of_sale_code' => [
                'required',
                'integer',
                'min:0',
                'max:2147483647',
                Rule::unique('sin_points_of_sale', 'point_of_sale_code')
                    ->where('company_id', $companyId)
                    ->where('sin_branch_id', $branch?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'point_of_sale_code.unique' => 'Ya existe un punto de venta con ese numero en la sucursal.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
        ]);
    }
}
