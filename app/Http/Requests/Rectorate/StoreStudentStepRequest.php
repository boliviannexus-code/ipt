<?php

namespace App\Http\Requests\Rectorate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('rectorate.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'student_identity_document' => ['required', 'string', 'max:30'],
            'student_first_name' => ['required', 'string', 'max:100'],
            'student_paternal_surname' => ['required', 'string', 'max:100'],
            'student_maternal_surname' => ['nullable', 'string', 'max:100'],
            'student_birth_date' => ['required', 'date', 'before:today'],
            'student_email' => ['nullable', 'email', 'max:255'],
            'student_phone' => ['nullable', 'required_if:primary_contact_type,Estudiante', 'string', 'max:30'],
            'student_relationship' => ['required', Rule::in(['Titular', 'Hijo/a', 'Hermano/a', 'Nieto/a', 'Sobrino/a', 'Otro'])],
            'student_gender' => ['required', Rule::in(['Femenino', 'Masculino', 'Otro'])],
            'primary_contact_type' => ['required', Rule::in(['Titular', 'Estudiante', 'Otro'])],
            'reference_first_name' => ['nullable', 'required_if:primary_contact_type,Otro', 'string', 'max:100'],
            'reference_last_name' => ['nullable', 'required_if:primary_contact_type,Otro', 'string', 'max:150'],
            'reference_relationship' => ['nullable', 'required_if:primary_contact_type,Otro', 'string', 'max:60'],
            'reference_phone' => ['nullable', 'required_if:primary_contact_type,Otro', 'string', 'max:30'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $values = [];
        foreach (array_keys($this->rules()) as $field) {
            if (is_string($this->input($field))) {
                $values[$field] = trim($this->input($field));
            }
        }
        $this->merge($values);
    }
}
