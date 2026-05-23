<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label" for="modal-measurement-unit-name">Nombre</label>
        <input class="form-control" id="modal-measurement-unit-name" name="name" value="{{ old('name', $measurementUnit->name ?? '') }}" autocomplete="new-password" data-lpignore="true" data-1p-ignore="true" required>
        <div class="invalid-feedback" data-error-for="name"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="modal-measurement-unit-abbreviation">Abreviatura</label>
        <input class="form-control" id="modal-measurement-unit-abbreviation" name="abbreviation" value="{{ old('abbreviation', $measurementUnit->abbreviation ?? '') }}" autocomplete="off" data-lpignore="true" data-1p-ignore="true" required>
        <div class="invalid-feedback" data-error-for="abbreviation"></div>
    </div>

    <div class="col-12">
        <input type="hidden" name="is_active" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" id="modal-measurement-unit-is-active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $measurementUnit->is_active ?? true))>
            <label class="form-check-label" for="modal-measurement-unit-is-active">Activo</label>
            <div class="invalid-feedback d-block" data-error-for="is_active"></div>
        </div>
    </div>
</div>
