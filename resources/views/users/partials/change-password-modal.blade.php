<form method="POST" action="{{ route('users.change-password', $user) }}" data-ajax-form data-refresh-url="{{ route('users.index') }}" novalidate>
    @csrf
    @method('PATCH')

    <p class="text-body-secondary">Cambiar contraseña para <strong>{{ $user->name }}</strong>.</p>

    <div class="mb-3">
        <label class="form-label" for="password">Nueva contraseña</label>
        <input class="form-control" id="password" name="password" type="password" required>
        <div class="invalid-feedback" data-error-for="password"></div>
    </div>

    <div class="mb-4">
        <label class="form-label" for="password_confirmation">Confirmar contraseña</label>
        <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" required>
        <div class="invalid-feedback" data-error-for="password_confirmation"></div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" type="submit">
            <span class="spinner-border spinner-border-sm me-2 d-none" data-submit-spinner aria-hidden="true"></span>
            Cambiar contraseña
        </button>
    </div>
</form>
