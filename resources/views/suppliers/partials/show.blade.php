<dl class="row mb-0">
    <dt class="col-sm-3">Nombre</dt>
    <dd class="col-sm-9">{{ $supplier->name }}</dd>

    <dt class="col-sm-3">Empresa</dt>
    <dd class="col-sm-9">{{ $supplier->company_name ?: '-' }}</dd>

    <dt class="col-sm-3">Email</dt>
    <dd class="col-sm-9">{{ $supplier->email ?: '-' }}</dd>

    <dt class="col-sm-3">Telefono</dt>
    <dd class="col-sm-9">{{ $supplier->phone ?: '-' }}</dd>

    <dt class="col-sm-3">Direccion</dt>
    <dd class="col-sm-9">{{ $supplier->address ?: '-' }}</dd>

    <dt class="col-sm-3">Estado</dt>
    <dd class="col-sm-9">
        <span class="badge text-bg-{{ $supplier->is_active ? 'success' : 'secondary' }}">
            {{ $supplier->is_active ? 'Activo' : 'Inactivo' }}
        </span>
    </dd>

    <dt class="col-sm-3">Creado</dt>
    <dd class="col-sm-9">{{ $supplier->created_at?->format('Y-m-d H:i') }}</dd>
</dl>
