<dl class="row mb-0">
    <dt class="col-sm-3">Presentacion</dt>
    <dd class="col-sm-9">{{ $productPresentation->name }}</dd>
    <dt class="col-sm-3">Equivalencia</dt>
    <dd class="col-sm-9">1 {{ $productPresentation->name }} = {{ $productPresentation->units_per_package }} unidades base</dd>
    <dt class="col-sm-3">Estado</dt>
    <dd class="col-sm-9">{{ $productPresentation->is_active ? 'Activo' : 'Inactivo' }}</dd>
</dl>
