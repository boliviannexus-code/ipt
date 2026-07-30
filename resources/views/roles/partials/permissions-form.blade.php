<form method="POST" action="{{ route('roles.permissions', $role) }}" data-ajax-form data-refresh-url="{{ route('roles.index') }}" novalidate>
    @csrf
    @method('PATCH')
    <div class="role-access-context">
        <span class="role-access-avatar" aria-hidden="true"><i class="ti ti-user-shield"></i></span>
        <div>
            <span class="text-body-secondary small">Estas configurando el acceso de</span>
            <h3 class="mb-0">{{ role_label($role->name) }}</h3>
        </div>
    </div>
    @include('roles.partials.permission-checkboxes')
    <div class="permission-form-actions">
        <span class="text-body-secondary small"><i class="ti ti-info-circle me-1" aria-hidden="true"></i>Los cambios se aplicaran a todos los usuarios con este rol.</span>
        <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" type="submit"><span class="spinner-border spinner-border-sm me-2 d-none" data-submit-spinner></span>Guardar permisos</button>
        </div>
    </div>
</form>
