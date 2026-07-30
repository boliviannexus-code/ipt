<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label" for="name">Nombre</label>
        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $product->name ?? '') }}" autocomplete="new-password" data-lpignore="true" data-1p-ignore="true" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="barcode">Codigo de barras</label>
        <input class="form-control @error('barcode') is-invalid @enderror" id="barcode" name="barcode" value="{{ old('barcode', $product->barcode ?? '') }}" autocomplete="off" data-lpignore="true" data-1p-ignore="true">
        @error('barcode')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="category_id">Categoria</label>
        <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
            <option value="">Seleccionar</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id ?? 0) === $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        @error('category_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="measurement_unit_id">Unidad de medida</label>
        <select class="form-select @error('measurement_unit_id') is-invalid @enderror" id="measurement_unit_id" name="measurement_unit_id" data-tom-select data-placeholder="Seleccionar" required>
            <option value="">Seleccionar</option>
            @foreach ($measurementUnits as $unit)
                <option value="{{ $unit->id }}" @selected((int) old('measurement_unit_id', $product->measurement_unit_id ?? 0) === $unit->id)>
                    {{ $unit->name }} ({{ $unit->abbreviation }})
                </option>
            @endforeach
        </select>
        @error('measurement_unit_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label" for="purchase_price">Precio compra</label>
        <input class="form-control @error('purchase_price') is-invalid @enderror" id="purchase_price" name="purchase_price" type="number" step="0.01" min="0" value="{{ old('purchase_price', $product->purchase_price ?? 0) }}" autocomplete="off" data-lpignore="true" data-1p-ignore="true" required>
        @error('purchase_price')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label" for="sale_price">Precio venta</label>
        <input class="form-control @error('sale_price') is-invalid @enderror" id="sale_price" name="sale_price" type="number" step="0.01" min="0" value="{{ old('sale_price', $product->sale_price ?? 0) }}" autocomplete="off" data-lpignore="true" data-1p-ignore="true" required>
        @error('sale_price')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label" for="minimum_stock">Stock minimo</label>
        <input class="form-control @error('minimum_stock') is-invalid @enderror" id="minimum_stock" name="minimum_stock" type="number" min="0" value="{{ old('minimum_stock', $product->minimum_stock ?? 0) }}" autocomplete="off" data-lpignore="true" data-1p-ignore="true" required>
        @error('minimum_stock')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-9">
        <label class="form-label" for="image">Imagen principal</label>
        <input class="form-control @error('image') is-invalid @enderror" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
        <div class="form-hint">Podrás revisar o quitar la selección antes de guardar. La imagen se optimizará a WebP.</div>
        <div class="form-hint">JPG, PNG o WebP. Maximo 2 MB. Se optimiza automaticamente.</div>
        @error('image')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        @if (($product ?? null)?->image_url)
            <div class="d-flex align-items-center gap-3 mt-2">
                <img class="product-image-preview" src="{{ $product->image_url }}" alt="{{ $product->name }}">
                <label class="form-check m-0">
                    <input class="form-check-input" name="remove_image" type="checkbox" value="1" @checked(old('remove_image'))>
                    <span class="form-check-label">Quitar imagen actual</span>
                </label>
            </div>
        @endif
        @error('remove_image')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-12">
        <label class="form-label" for="description">Descripcion</label>
        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" autocomplete="off" data-lpignore="true" data-1p-ignore="true">{{ old('description', $product->description ?? '') }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-12">
        <input type="hidden" name="is_active" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $product->is_active ?? true))>
            <label class="form-check-label" for="is_active">Activo</label>
        </div>
    </div>
</div>
<div class="d-flex gap-2 mt-4">
    <button class="btn btn-primary" type="submit">Guardar</button>
    <a class="btn btn-outline-secondary" href="{{ route('products.index') }}">Cancelar</a>
</div>
