@php
    $selectedPermissions = collect(old('permissions', isset($role) ? $role->permissions->pluck('name')->all() : []));
@endphp

<div class="accordion" id="permissionsAccordion">
    @foreach ($permissionGroups as $module => $permissions)
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#permissions-{{ str($module)->slug() }}">
                    {{ permission_module_label($module) }}
                </button>
            </h2>
            <div class="accordion-collapse collapse" id="permissions-{{ str($module)->slug() }}" data-bs-parent="#permissionsAccordion">
                <div class="accordion-body">
                    <div class="row g-2">
                        @foreach ($permissions as $permission)
                            <div class="col-md-6">
                                <div class="form-check border rounded p-2 ps-5 bg-light">
                                    <input class="form-check-input" id="permission-{{ $permission->id }}" name="permissions[]" type="checkbox" value="{{ $permission->name }}" @checked($selectedPermissions->contains($permission->name))>
                                    <label class="form-check-label" for="permission-{{ $permission->id }}">{{ permission_label($permission->name) }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
<div class="invalid-feedback d-block" data-error-for="permissions">{{ ($errors ?? null)?->first('permissions') }}</div>
