<dl class="row mb-0">
    <dt class="col-sm-3">Nombre</dt>
    <dd class="col-sm-9">{{ $category->name }}</dd>

    <dt class="col-sm-3">Descripcion</dt>
    <dd class="col-sm-9">{{ $category->description ?: 'Sin descripcion' }}</dd>

    <dt class="col-sm-3">Estado</dt>
    <dd class="col-sm-9">
        <span class="badge text-bg-{{ $category->is_active ? 'success' : 'secondary' }}">
            {{ $category->is_active ? 'Activo' : 'Inactivo' }}
        </span>
    </dd>

    <dt class="col-sm-3">Creado</dt>
    <dd class="col-sm-9">{{ $category->created_at?->format('Y-m-d H:i') }}</dd>
</dl>
