<a class="btn btn-outline-secondary btn-sm" href="{{ route('measurement-units.show', $measurementUnit) }}" data-modal-url="{{ route('measurement-units.show', $measurementUnit) }}" data-modal-title="Detalle de unidad">Ver</a>
@can('measurement-units.update')
    <a class="btn btn-outline-primary btn-sm" href="{{ route('measurement-units.edit', $measurementUnit) }}" data-modal-url="{{ route('measurement-units.edit', $measurementUnit) }}" data-modal-title="Editar unidad">Editar</a>
@endcan
@can('measurement-units.delete')
    <form class="d-inline" method="POST" action="{{ route('measurement-units.destroy', $measurementUnit) }}" data-confirm-delete="Eliminar unidad de medida?">
        @csrf
        @method('DELETE')
        <button class="btn btn-outline-danger btn-sm" type="submit">Eliminar</button>
    </form>
@endcan
