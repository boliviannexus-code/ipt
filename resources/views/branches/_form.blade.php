<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label" for="name">Nombre</label>
        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $branch->name ?? '') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="code">Codigo</label>
        <input class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $branch->code ?? '') }}" required>
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="phone">Telefono</label>
        <input class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $branch->phone ?? '') }}">
        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-8">
        <label class="form-label" for="address">Direccion</label>
        <input class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', $branch->address ?? '') }}">
        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<input type="hidden" name="is_active" value="0">
<div class="form-check form-switch my-4">
    <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $branch->is_active ?? true))>
    <label class="form-check-label" for="is_active">Activo</label>
</div>

<div class="d-flex gap-2">
    <button class="btn btn-primary" type="submit">Guardar</button>
    <a class="btn btn-outline-secondary" href="{{ route('branches.index') }}">Cancelar</a>
</div>
