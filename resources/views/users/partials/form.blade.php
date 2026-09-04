@php
    $selectedRoles = collect(old('roles', isset($user) ? $user->roles->pluck('name')->all() : []));
    $isCreate = ($mode ?? 'create') === 'create';
@endphp

<div class="row g-3">
    <div class="col-12">
        <label class="form-label" for="user-personnel">Personal</label>
        <select class="form-select" id="user-personnel" name="personnel_id" required data-user-personnel-select>
            <option value="">Seleccione personal</option>
            @foreach($personnelOptions as $person)
                <option value="{{ $person->id }}" data-name="{{ $person->full_name }}" data-ci="{{ $person->identity_document }}" data-email="{{ $person->email }}" data-phone="{{ $person->phone }}" data-company="{{ $person->company->name }}" data-area="{{ $person->position->area->name }}" data-position="{{ $person->position->name }}" @selected((int)old('personnel_id',$selectedPersonnelId)===$person->id)>{{ $person->full_name }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback d-block" data-error-for="personnel_id">{{ ($errors ?? null)?->first('personnel_id') }}</div>
    </div>

    <div class="col-12 d-none" data-user-personnel-details>
        <div class="card bg-light-subtle border">
            <div class="card-header py-2"><h3 class="card-title mb-0"><i class="ti ti-id-badge-2 me-2 text-primary"></i>Datos del personal seleccionado</h3></div>
            <div class="card-body"><div class="row g-3">
                <div class="col-md-6"><div class="text-secondary small">Nombre completo</div><div class="fw-semibold" data-personnel-detail="name">—</div></div>
                <div class="col-md-3"><div class="text-secondary small">CI</div><div class="fw-semibold" data-personnel-detail="ci">—</div></div>
                <div class="col-md-3"><div class="text-secondary small">Teléfono</div><div class="fw-semibold" data-personnel-detail="phone">—</div></div>
                <div class="col-md-6"><div class="text-secondary small">Correo de acceso</div><div class="fw-semibold text-break" data-personnel-detail="email">—</div></div>
                <div class="col-md-6"><div class="text-secondary small">Empresa</div><div class="fw-semibold" data-personnel-detail="company">—</div></div>
                <div class="col-md-6"><div class="text-secondary small">Área</div><div class="fw-semibold" data-personnel-detail="area">—</div></div>
                <div class="col-md-6"><div class="text-secondary small">Cargo</div><div class="fw-semibold" data-personnel-detail="position">—</div></div>
            </div></div>
        </div>
    </div>

    @if ($isCreate)
        <div class="col-md-6">
            <label class="form-label" for="user-password">Contraseña temporal</label>
            <input class="form-control" id="user-password" name="password" type="password" minlength="8" required autocomplete="new-password">
            <div class="form-text">Mínimo 8 caracteres. Entrégala al usuario por un medio seguro.</div>
            <div class="invalid-feedback d-block" data-error-for="password">{{ ($errors ?? null)?->first('password') }}</div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="user-password-confirmation">Confirmar contraseña</label>
            <input class="form-control" id="user-password-confirmation" name="password_confirmation" type="password" minlength="8" required autocomplete="new-password">
            <div class="invalid-feedback d-block" data-error-for="password_confirmation">{{ ($errors ?? null)?->first('password_confirmation') }}</div>
        </div>
        <div class="col-12"><div class="alert alert-info mb-0"><i class="ti ti-key me-2"></i>El usuario deberá cambiar esta contraseña temporal después de ingresar.</div></div>
    @endif

    <div class="col-12">
        <input type="hidden" name="is_active" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" id="user-is-active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $user->is_active ?? true))>
            <label class="form-check-label" for="user-is-active">Usuario activo</label>
            <div class="invalid-feedback d-block" data-error-for="is_active">{{ ($errors ?? null)?->first('is_active') }}</div>
        </div>
    </div>

    <div class="col-12">
        <label class="form-label">Roles</label>
        <div class="row g-2">
            @foreach ($roles as $role)
                <div class="col-md-4">
                    <div class="form-check border rounded p-2 ps-5 bg-light">
                        <input class="form-check-input" id="role-{{ $role->id }}" name="roles[]" type="checkbox" value="{{ $role->name }}" @checked($selectedRoles->contains($role->name))>
                        <label class="form-check-label" for="role-{{ $role->id }}">{{ role_label($role->name) }}</label>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="invalid-feedback d-block" data-error-for="roles">{{ ($errors ?? null)?->first('roles') }}</div>
    </div>
</div>
