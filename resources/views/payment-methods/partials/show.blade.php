<dl class="row mb-0">
    <dt class="col-sm-4">Nombre</dt>
    <dd class="col-sm-8">{{ $paymentMethod->name }}</dd>

    <dt class="col-sm-4">Estado</dt>
    <dd class="col-sm-8">{{ $paymentMethod->is_active ? 'Activo' : 'Inactivo' }}</dd>

    <dt class="col-sm-4">Creado</dt>
    <dd class="col-sm-8">{{ $paymentMethod->created_at?->format('Y-m-d H:i') }}</dd>
</dl>
