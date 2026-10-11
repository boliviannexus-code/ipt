<?php

namespace App\Http\Requests\User;

use App\Models\Personnel;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('users.create') ?? false) && CompanyContext::canOperate($this->user());
    }

    public function rules(): array
    {
        return [
            'personnel_id' => ['required', 'exists:personnel,id', 'unique:users,personnel_id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:8'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,name'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $personnel = Personnel::query()->find($this->integer('personnel_id'));
            if (! $personnel) {
                return;
            }
            if (! CompanyContext::belongsToUser($personnel->company_id, $this->user())) {
                $validator->errors()->add('personnel_id', 'El personal no pertenece a una empresa permitida.');
            }
            if (blank($personnel->email)) {
                $validator->errors()->add('personnel_id', 'El personal debe tener un correo registrado antes de asignarle usuario.');
            } elseif (User::withTrashed()->where('email', $personnel->email)->exists()) {
                $validator->errors()->add('personnel_id', 'El correo del personal ya está asignado a otro usuario.');
            }
        });
    }
}
