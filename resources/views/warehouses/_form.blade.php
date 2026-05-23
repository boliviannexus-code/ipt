<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="branch_id">Sucursal</label>
        <select class="form-select @error('branch_id') is-invalid @enderror" id="branch_id" name="branch_id" required>
            <option value="">Selecciona una sucursal</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((int) old('branch_id', $warehouse->branch_id ?? 0) === $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
        @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="name">Nombre</label>
        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $warehouse->name ?? '') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2">
        <label class="form-label" for="code">Codigo</label>
        <input class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $warehouse->code ?? '') }}" required>
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<input type="hidden" name="is_active" value="0">
<div class="form-check form-switch my-4">
    <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $warehouse->is_active ?? true))>
    <label class="form-check-label" for="is_active">Activo</label>
</div>

<div class="d-flex gap-2">
    <button class="btn btn-primary" type="submit">Guardar</button>
    <a class="btn btn-outline-secondary" href="{{ route('warehouses.index') }}">Cancelar</a>
</div>
