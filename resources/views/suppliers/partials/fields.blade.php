<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label" for="modal-supplier-name">Nombre</label>
        <input class="form-control" id="modal-supplier-name" name="name" value="{{ old('name', $supplier->name ?? '') }}" autocomplete="new-password" data-lpignore="true" data-1p-ignore="true" required>
        <div class="invalid-feedback" data-error-for="name"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="modal-supplier-company-name">Empresa</label>
        <input class="form-control" id="modal-supplier-company-name" name="company_name" value="{{ old('company_name', $supplier->company_name ?? '') }}" autocomplete="off" data-lpignore="true" data-1p-ignore="true">
        <div class="invalid-feedback" data-error-for="company_name"></div>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="modal-supplier-email">Email</label>
        <input class="form-control" id="modal-supplier-email" name="email" type="email" value="{{ old('email', $supplier->email ?? '') }}" autocomplete="off" data-lpignore="true" data-1p-ignore="true">
        <div class="invalid-feedback" data-error-for="email"></div>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="modal-supplier-phone">Telefono</label>
        <input class="form-control" id="modal-supplier-phone" name="phone" value="{{ old('phone', $supplier->phone ?? '') }}" autocomplete="off" data-lpignore="true" data-1p-ignore="true">
        <div class="invalid-feedback" data-error-for="phone"></div>
    </div>

    <div class="col-12">
        <label class="form-label" for="modal-supplier-address">Direccion</label>
        <input class="form-control" id="modal-supplier-address" name="address" value="{{ old('address', $supplier->address ?? '') }}" autocomplete="off" data-lpignore="true" data-1p-ignore="true">
        <div class="invalid-feedback" data-error-for="address"></div>
    </div>

    <div class="col-12">
        <input type="hidden" name="is_active" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" id="modal-supplier-is-active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $supplier->is_active ?? true))>
            <label class="form-check-label" for="modal-supplier-is-active">Activo</label>
            <div class="invalid-feedback d-block" data-error-for="is_active"></div>
        </div>
    </div>
</div>
