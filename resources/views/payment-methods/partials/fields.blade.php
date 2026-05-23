<div class="row g-3">
    <div class="col-12">
        <label class="form-label" for="modal-payment-method-name">Nombre</label>
        <input class="form-control" id="modal-payment-method-name" name="name" value="{{ old('name', $paymentMethod->name ?? '') }}" autocomplete="new-password" data-lpignore="true" data-1p-ignore="true" required>
        <div class="invalid-feedback" data-error-for="name"></div>
    </div>

    <div class="col-12">
        <input type="hidden" name="is_active" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" id="modal-payment-method-is-active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $paymentMethod->is_active ?? true))>
            <label class="form-check-label" for="modal-payment-method-is-active">Activo</label>
            <div class="invalid-feedback d-block" data-error-for="is_active"></div>
        </div>
    </div>
</div>
