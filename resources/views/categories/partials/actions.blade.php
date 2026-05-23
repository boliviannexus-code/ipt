<a class="btn btn-outline-secondary btn-sm" href="{{ route('categories.show', $category) }}" data-modal-url="{{ route('categories.show', $category) }}" data-modal-title="Detalle de categoria">Ver</a>
@can('categories.update')
    <a class="btn btn-outline-primary btn-sm" href="{{ route('categories.edit', $category) }}" data-modal-url="{{ route('categories.edit', $category) }}" data-modal-title="Editar categoria">Editar</a>
@endcan
@can('categories.delete')
    <form class="d-inline" method="POST" action="{{ route('categories.destroy', $category) }}" data-confirm-delete="Eliminar categoria?">
        @csrf
        @method('DELETE')
        <button class="btn btn-outline-danger btn-sm" type="submit">Eliminar</button>
    </form>
@endcan
