<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\CompanyContext;

class UpdateMeasurementUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('measurement-units.update') ?? false) && CompanyContext::canOperate($this->user());
    }

    public function rules(): array
    {
        $unitId = $this->route('measurement_unit')?->id ?? $this->route('measurement_unit');
        $companyId = CompanyContext::id($this->user());

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('measurement_units', 'name')->where(fn ($query) => $query->where('company_id', $companyId))->ignore($unitId)],
            'abbreviation' => ['required', 'string', 'max:20', Rule::unique('measurement_units', 'abbreviation')->where(fn ($query) => $query->where('company_id', $companyId))->ignore($unitId)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
