<a class="btn btn-outline-secondary btn-sm" href="{{ route('suppliers.show', $supplier) }}" data-modal-url="{{ route('suppliers.show', $supplier) }}" data-modal-title="Detalle de proveedor">Ver</a>
@can('suppliers.update')
    <a class="btn btn-outline-primary btn-sm" href="{{ route('suppliers.edit', $supplier) }}" data-modal-url="{{ route('suppliers.edit', $supplier) }}" data-modal-title="Editar proveedor">Editar</a>
@endcan
@can('suppliers.delete')
    <form class="d-inline" method="POST" action="{{ route('suppliers.destroy', $supplier) }}" data-confirm-delete="Eliminar proveedor?">
        @csrf
        @method('DELETE')
        <button class="btn btn-outline-danger btn-sm" type="submit">Eliminar</button>
    </form>
@endcan
