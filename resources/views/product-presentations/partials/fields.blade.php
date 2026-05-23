<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label" for="modal-product-presentation-name">Presentacion</label>
        <input class="form-control" id="modal-product-presentation-name" name="name" value="{{ old('name', $productPresentation->name ?? '') }}" placeholder="Caja x 10" required>
        <div class="invalid-feedback" data-error-for="name"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="modal-product-presentation-units">Unidades por empaque</label>
        <input class="form-control" id="modal-product-presentation-units" name="units_per_package" type="number" min="1" value="{{ old('units_per_package', $productPresentation->units_per_package ?? 1) }}" required>
        <div class="invalid-feedback" data-error-for="units_per_package"></div>
    </div>
    <div class="col-12">
        <input type="hidden" name="is_active" value="0">
        <label class="form-check form-switch mb-2">
            <input class="form-check-input" name="is_active" type="checkbox" value="1" @checked(old('is_active', $productPresentation->is_active ?? true))>
            <span class="form-check-label">Activo</span>
        </label>
    </div>
</div>
