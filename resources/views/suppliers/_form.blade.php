<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label" for="name">Nombre</label>
        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $supplier->name ?? '') }}" autocomplete="new-password" data-lpignore="true" data-1p-ignore="true" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="company_name">Empresa</label>
        <input class="form-control @error('company_name') is-invalid @enderror" id="company_name" name="company_name" value="{{ old('company_name', $supplier->company_name ?? '') }}" autocomplete="off" data-lpignore="true" data-1p-ignore="true">
        @error('company_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="email">Email</label>
        <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email', $supplier->email ?? '') }}" autocomplete="off" data-lpignore="true" data-1p-ignore="true">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="phone">Telefono</label>
        <input class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $supplier->phone ?? '') }}" autocomplete="off" data-lpignore="true" data-1p-ignore="true">
        @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label" for="address">Direccion</label>
        <input class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', $supplier->address ?? '') }}" autocomplete="off" data-lpignore="true" data-1p-ignore="true">
        @error('address')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <input type="hidden" name="is_active" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $supplier->is_active ?? true))>
            <label class="form-check-label" for="is_active">Activo</label>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button class="btn btn-primary" type="submit">Guardar</button>
    <a class="btn btn-outline-secondary" href="{{ route('suppliers.index') }}">Cancelar</a>
</div>
