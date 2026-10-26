<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Rectorate\StoreStudentStepRequest;
use App\Support\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAcademicPersonalDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('academic-personal-data.edit') ?? false;
    }

    public function rules(): array
    {
        $rules = (new StoreStudentStepRequest)->rules();
        $rules['student_identity_document'][] = Rule::unique('students', 'identity_document')
            ->where('company_id', CompanyContext::id($this->user()))
            ->ignore($this->route('application')->student_id);

        return [...$rules,
            'identity_document' => ['required', 'string', 'regex:/^\d{5,10}$/'],
            'first_name' => ['required', 'string', 'max:100'],
            'paternal_surname' => ['required', 'string', 'max:100'],
            'maternal_surname' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before:today'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $values = [];
        foreach ($this->all() as $key => $value) {
            if (is_string($value)) {
                $values[$key] = trim($value) === '' ? null : trim($value);
            }
        }
        $this->merge($values);
    }
}
