<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label" for="name">Nombre</label>
        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $measurementUnit->name ?? '') }}" autocomplete="new-password" data-lpignore="true" data-1p-ignore="true" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="abbreviation">Abreviatura</label>
        <input class="form-control @error('abbreviation') is-invalid @enderror" id="abbreviation" name="abbreviation" value="{{ old('abbreviation', $measurementUnit->abbreviation ?? '') }}" autocomplete="off" data-lpignore="true" data-1p-ignore="true" required>
        @error('abbreviation')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <input type="hidden" name="is_active" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $measurementUnit->is_active ?? true))>
            <label class="form-check-label" for="is_active">Activo</label>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button class="btn btn-primary" type="submit">Guardar</button>
    <a class="btn btn-outline-secondary" href="{{ route('measurement-units.index') }}">Cancelar</a>
</div>
