@php
    $selectedProductId = $item['product_id'] ?? null;
    $selectedPresentationId = $item['presentation_id'] ?? null;
@endphp

<tr data-purchase-item-row>
    <td class="purchase-product-cell">
        <select class="form-select @error('items.'.$index.'.product_id') is-invalid @enderror" name="items[{{ $index }}][product_id]" data-tom-select data-purchase-product data-placeholder="Producto">
            <option value="">Seleccionar</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" data-price="{{ $product->purchase_price }}" data-unit="{{ $product->measurementUnit?->abbreviation ?? 'u' }}" @selected($selectedProductId == $product->id)>
                    {{ $product->name }}
                </option>
            @endforeach
        </select>
        @error('items.'.$index.'.product_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </td>
    <td class="purchase-presentation-cell">
        <select class="form-select @error('items.'.$index.'.presentation_id') is-invalid @enderror" name="items[{{ $index }}][presentation_id]" data-tom-select data-purchase-presentation data-placeholder="Presentacion">
            <option value="">Seleccionar</option>
            @foreach ($presentations as $presentation)
                <option value="{{ $presentation->id }}" data-units="{{ $presentation->units_per_package }}" @selected($selectedPresentationId == $presentation->id)>
                    {{ $presentation->name }}
                </option>
            @endforeach
        </select>
        @error('items.'.$index.'.presentation_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </td>
    <td class="text-end purchase-unit-calc" data-unit-calculation>0 u.</td>
    <td>
        <input class="form-control text-end @error('items.'.$index.'.package_quantity') is-invalid @enderror" name="items[{{ $index }}][package_quantity]" type="number" min="1" step="1" value="{{ $item['package_quantity'] ?? 1 }}" data-package-quantity required>
        @error('items.'.$index.'.package_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </td>
    <td>
        <input class="form-control text-end @error('items.'.$index.'.unit_price') is-invalid @enderror" name="items[{{ $index }}][unit_price]" type="number" min="0" step="0.01" value="{{ $item['unit_price'] ?? '' }}" data-unit-price data-auto-price="{{ isset($item['unit_price']) ? '0' : '1' }}" required>
        @error('items.'.$index.'.unit_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </td>
    <td class="text-end fw-semibold" data-line-subtotal>0.00</td>
    <td class="text-end">
        <button class="btn btn-outline-danger btn-icon" type="button" data-remove-purchase-item aria-label="Quitar producto" title="Quitar producto">
            <i class="ti ti-trash"></i>
        </button>
    </td>
</tr>
