<div class="mb-4">
    @if ($product->image_url)
        <img class="product-image-show" src="{{ $product->image_url }}" alt="{{ $product->name }}">
    @else
        <span class="product-image-show product-image-placeholder" aria-label="Sin imagen">
            <i class="ti ti-photo" aria-hidden="true"></i>
        </span>
    @endif
</div>

<dl class="row mb-0">
    <dt class="col-sm-3">Nombre</dt>
    <dd class="col-sm-9">{{ $product->name }}</dd>

    <dt class="col-sm-3">Categoria</dt>
    <dd class="col-sm-9">{{ $product->category?->name }}</dd>

    <dt class="col-sm-3">Unidad de medida</dt>
    <dd class="col-sm-9">{{ $product->measurementUnit ? $product->measurementUnit->name.' ('.$product->measurementUnit->abbreviation.')' : '-' }}</dd>

    <dt class="col-sm-3">Codigo de barras</dt>
    <dd class="col-sm-9">{{ $product->barcode ?: '-' }}</dd>

    <dt class="col-sm-3">Descripcion</dt>
    <dd class="col-sm-9">{{ $product->description ?: 'Sin descripcion' }}</dd>

    <dt class="col-sm-3">Precio compra</dt>
    <dd class="col-sm-9">{{ money_format_decimal($product->purchase_price) }}</dd>

    <dt class="col-sm-3">Precio venta</dt>
    <dd class="col-sm-9">{{ money_format_decimal($product->sale_price) }}</dd>

    <dt class="col-sm-3">Stock minimo</dt>
    <dd class="col-sm-9">{{ $product->minimum_stock }}</dd>

    <dt class="col-sm-3">Estado</dt>
    <dd class="col-sm-9">
        <span class="badge text-bg-{{ $product->is_active ? 'success' : 'secondary' }}">
            {{ $product->is_active ? 'Activo' : 'Inactivo' }}
        </span>
    </dd>
</dl>
