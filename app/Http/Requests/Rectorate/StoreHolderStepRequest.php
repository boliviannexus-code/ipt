<?php

namespace App\Http\Requests\Rectorate;

use App\Support\CompanyContext;
use App\Support\SiatIdentityDocumentTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreHolderStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('rectorate.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'identity_document' => ['required', 'string', 'regex:/^\d{5,10}$/'],
            'first_name' => ['required', 'string', 'max:100'],
            'paternal_surname' => ['required', 'string', 'max:100'],
            'maternal_surname' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before:today'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'identity_document_type_code' => ['required', 'integer', 'min:1'],
            'document_number' => ['required', 'string', 'max:80'],
            'document_complement' => ['nullable', 'string', 'max:20'],
            'legal_name' => ['required', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];
        foreach (['identity_document', 'first_name', 'paternal_surname', 'maternal_surname', 'email', 'phone', 'document_number', 'document_complement', 'legal_name'] as $field) {
            if (is_string($this->input($field))) {
                $normalized[$field] = trim($this->input($field));
            }
        }
        if (($normalized['document_complement'] ?? null) === '') {
            $normalized['document_complement'] = null;
        }
        if (($normalized['maternal_surname'] ?? null) === '') {
            $normalized['maternal_surname'] = null;
        }
        $this->merge($normalized);
    }

    public function messages(): array
    {
        return [
            'identity_document.regex' => 'El CI del titular debe tener entre 5 y 10 dígitos.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['identity_document_type_code', 'document_number'])) {
                return;
            }
            $companyId = CompanyContext::id($this->user());
            $code = (string) $this->input('identity_document_type_code');
            $number = (string) $this->input('document_number');
            if (! SiatIdentityDocumentTypes::canBeUsed($companyId, $code)) {
                $validator->errors()->add('identity_document_type_code', 'Selecciona un tipo de documento activo del catálogo SIAT.');
            } elseif (SiatIdentityDocumentTypes::requiresIdentityCardDigits($code) && ! preg_match('/^\d{5,10}$/', $number)) {
                $validator->errors()->add('document_number', 'El carnet debe tener entre 5 y 10 dígitos.');
            } elseif (SiatIdentityDocumentTypes::requiresNitDigits($code) && ! preg_match('/^\d{7,13}$/', $number)) {
                $validator->errors()->add('document_number', 'El NIT debe tener entre 7 y 13 dígitos.');
            }
        });
    }
}
