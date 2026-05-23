<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label" for="modal-product-name">Nombre</label>
        <input class="form-control" id="modal-product-name" name="name" value="{{ old('name', $product->name ?? '') }}" autocomplete="new-password" data-lpignore="true" data-1p-ignore="true" required>
        <div class="invalid-feedback" data-error-for="name"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="modal-product-barcode">Codigo de barras</label>
        <input class="form-control" id="modal-product-barcode" name="barcode" value="{{ old('barcode', $product->barcode ?? '') }}" autocomplete="off" data-lpignore="true" data-1p-ignore="true">
        <div class="invalid-feedback" data-error-for="barcode"></div>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="modal-product-category">Categoria</label>
        <select class="form-select" id="modal-product-category" name="category_id" required>
            <option value="">Seleccionar</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id ?? 0) === $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="category_id"></div>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="modal-product-measurement-unit">Unidad de medida</label>
        <select class="form-select" id="modal-product-measurement-unit" name="measurement_unit_id" data-tom-select data-placeholder="Seleccionar" required>
            <option value="">Seleccionar</option>
            @foreach ($measurementUnits as $unit)
                <option value="{{ $unit->id }}" @selected((int) old('measurement_unit_id', $product->measurement_unit_id ?? 0) === $unit->id)>
                    {{ $unit->name }} ({{ $unit->abbreviation }})
                </option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="measurement_unit_id"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="modal-product-purchase-price">Precio compra</label>
        <input class="form-control" id="modal-product-purchase-price" name="purchase_price" type="number" step="0.01" min="0" value="{{ old('purchase_price', $product->purchase_price ?? 0) }}" autocomplete="off" data-lpignore="true" data-1p-ignore="true" required>
        <div class="invalid-feedback" data-error-for="purchase_price"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="modal-product-sale-price">Precio venta</label>
        <input class="form-control" id="modal-product-sale-price" name="sale_price" type="number" step="0.01" min="0" value="{{ old('sale_price', $product->sale_price ?? 0) }}" autocomplete="off" data-lpignore="true" data-1p-ignore="true" required>
        <div class="invalid-feedback" data-error-for="sale_price"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="modal-product-minimum-stock">Stock minimo</label>
        <input class="form-control" id="modal-product-minimum-stock" name="minimum_stock" type="number" min="0" value="{{ old('minimum_stock', $product->minimum_stock ?? 0) }}" autocomplete="off" data-lpignore="true" data-1p-ignore="true" required>
        <div class="invalid-feedback" data-error-for="minimum_stock"></div>
    </div>

    <div class="col-md-9">
        <label class="form-label" for="modal-product-image">Imagen principal</label>
        <input class="form-control" id="modal-product-image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
        <div class="form-hint">JPG, PNG o WebP. Maximo 2 MB. Se optimiza automaticamente.</div>
        <div class="invalid-feedback" data-error-for="image"></div>
        @if (($product ?? null)?->image_url)
            <div class="d-flex align-items-center gap-3 mt-2">
                <img class="product-image-preview" src="{{ $product->image_url }}" alt="{{ $product->name }}">
                <label class="form-check m-0">
                    <input class="form-check-input" name="remove_image" type="checkbox" value="1" @checked(old('remove_image'))>
                    <span class="form-check-label">Quitar imagen actual</span>
                </label>
            </div>
            <div class="invalid-feedback d-block" data-error-for="remove_image"></div>
        @endif
    </div>

    <div class="col-md-12">
        <label class="form-label" for="modal-product-description">Descripcion</label>
        <textarea class="form-control" id="modal-product-description" name="description" rows="3" autocomplete="off" data-lpignore="true" data-1p-ignore="true">{{ old('description', $product->description ?? '') }}</textarea>
        <div class="invalid-feedback" data-error-for="description"></div>
    </div>

    <div class="col-12">
        <input type="hidden" name="is_active" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" id="modal-product-is-active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $product->is_active ?? true))>
            <label class="form-check-label" for="modal-product-is-active">Activo</label>
            <div class="invalid-feedback d-block" data-error-for="is_active"></div>
        </div>
    </div>
</div>
