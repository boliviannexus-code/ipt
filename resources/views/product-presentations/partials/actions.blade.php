<a class="btn btn-outline-secondary btn-sm" href="{{ route('product-presentations.show', $productPresentation) }}" data-modal-url="{{ route('product-presentations.show', $productPresentation) }}" data-modal-title="Detalle de presentacion">Ver</a>
@can('product-presentations.update')
    <a class="btn btn-outline-primary btn-sm" href="{{ route('product-presentations.edit', $productPresentation) }}" data-modal-url="{{ route('product-presentations.edit', $productPresentation) }}" data-modal-title="Editar presentacion">Editar</a>
@endcan
@can('product-presentations.delete')
    <form class="d-inline" method="POST" action="{{ route('product-presentations.destroy', $productPresentation) }}" data-confirm-delete="Eliminar presentacion?">
        @csrf
        @method('DELETE')
        <button class="btn btn-outline-danger btn-sm" type="submit">Eliminar</button>
    </form>
@endcan
