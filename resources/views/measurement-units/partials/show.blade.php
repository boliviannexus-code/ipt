<dl class="row mb-0">
    <dt class="col-sm-3">Nombre</dt>
    <dd class="col-sm-9">{{ $measurementUnit->name }}</dd>

    <dt class="col-sm-3">Abreviatura</dt>
    <dd class="col-sm-9">{{ $measurementUnit->abbreviation }}</dd>

    <dt class="col-sm-3">Estado</dt>
    <dd class="col-sm-9">
        <span class="badge text-bg-{{ $measurementUnit->is_active ? 'success' : 'secondary' }}">
            {{ $measurementUnit->is_active ? 'Activo' : 'Inactivo' }}
        </span>
    </dd>

    <dt class="col-sm-3">Creado</dt>
    <dd class="col-sm-9">{{ $measurementUnit->created_at?->format('Y-m-d H:i') }}</dd>
</dl>
