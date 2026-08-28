<?php

namespace App\Http\Requests\Reports;

use App\Support\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnrollmentReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('enrollment-reports.view') ?? false;
    }

    public function rules(): array
    {
        $companyId = CompanyContext::id($this->user());

        return [
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'campus_id' => ['nullable', 'integer', Rule::exists('campuses', 'id')->where('company_id', $companyId)],
            'program_id' => ['nullable', 'integer', Rule::exists('programs', 'id')->where('company_id', $companyId)],
            'plan_id' => ['nullable', 'integer', Rule::exists('plans', 'id')->where('company_id', $companyId)],
            'sales_executive_id' => ['nullable', 'integer', Rule::exists('personnel', 'id')->where('company_id', $companyId)],
            'commercial_origin_id' => ['nullable', 'integer', Rule::exists('commercial_origins', 'id')->where('company_id', $companyId)],
            'status' => ['nullable', Rule::in(['draft', 'completed'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'date_from' => $this->input('date_from', now()->startOfMonth()->toDateString()),
            'date_to' => $this->input('date_to', today()->toDateString()),
        ]);
    }
}
