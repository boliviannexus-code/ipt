<a class="btn btn-outline-secondary btn-sm" href="{{ route('products.show', $product) }}" data-modal-url="{{ route('products.show', $product) }}" data-modal-title="Detalle de producto">Ver</a>
@can('products.update')
    <a class="btn btn-outline-primary btn-sm" href="{{ route('products.edit', $product) }}" data-modal-url="{{ route('products.edit', $product) }}" data-modal-title="Editar producto">Editar</a>
@endcan
@can('products.delete')
    <form class="d-inline" method="POST" action="{{ route('products.destroy', $product) }}" data-confirm-delete="Eliminar producto?">
        @csrf
        @method('DELETE')
        <button class="btn btn-outline-danger btn-sm" type="submit">Eliminar</button>
    </form>
@endcan
