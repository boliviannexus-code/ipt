<div class="mb-3">
    <label class="form-label" for="modal-category-name">Nombre</label>
    <input class="form-control" id="modal-category-name" name="name" value="{{ old('name', $category->name ?? '') }}" required>
    <div class="invalid-feedback" data-error-for="name"></div>
</div>

<div class="mb-3">
    <label class="form-label" for="modal-category-description">Descripcion</label>
    <textarea class="form-control" id="modal-category-description" name="description" rows="4">{{ old('description', $category->description ?? '') }}</textarea>
    <div class="invalid-feedback" data-error-for="description"></div>
</div>

<input type="hidden" name="is_active" value="0">
<div class="form-check form-switch mb-4">
    <input class="form-check-input" id="modal-category-is-active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $category->is_active ?? true))>
    <label class="form-check-label" for="modal-category-is-active">Activo</label>
    <div class="invalid-feedback d-block" data-error-for="is_active"></div>
</div>
