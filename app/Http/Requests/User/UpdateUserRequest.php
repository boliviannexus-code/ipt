<?php

namespace App\Http\Requests\User;

use App\Models\Personnel;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('users.edit') ?? false) && CompanyContext::canOperate($this->user());
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('user');

        return [
            'personnel_id' => ['required', 'exists:personnel,id', Rule::unique('users', 'personnel_id')->ignore($userId)],
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
            $userId = $this->route('user')?->id ?? $this->route('user');
            if (blank($personnel->email)) {
                $validator->errors()->add('personnel_id', 'El personal debe tener un correo registrado.');
            } elseif (User::withTrashed()->where('email', $personnel->email)->whereKeyNot($userId)->exists()) {
                $validator->errors()->add('personnel_id', 'El correo del personal ya está asignado a otro usuario.');
            }
        });
    }
}
