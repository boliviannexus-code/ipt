<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('roles.edit') ?? false)
            && (! $this->hasAny(['permissions', 'permissions_submitted']) || ($this->user()?->can('roles.assign-permissions') ?? false));
    }

    protected function prepareForValidation(): void
    {
        if ($this->boolean('permissions_submitted') && ! $this->has('permissions')) {
            $this->merge(['permissions' => []]);
        }
    }

    public function rules(): array
    {
        $roleId = $this->route('role')?->id ?? $this->route('role');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($roleId)],
            'guard_name' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ];
    }
}
