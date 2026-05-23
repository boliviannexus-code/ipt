@if ($product->image_url)
    <img class="product-image-thumb" src="{{ $product->image_url }}" alt="{{ $product->name }}">
@else
    <span class="product-image-placeholder" aria-label="Sin imagen">
        <i class="ti ti-photo" aria-hidden="true"></i>
    </span>
@endif
