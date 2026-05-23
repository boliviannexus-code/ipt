<dl class="row mb-0">
    <dt class="col-sm-3">Nombre</dt>
    <dd class="col-sm-9">{{ permission_label($permission->name) }}</dd>
    <dt class="col-sm-3">Guard</dt>
    <dd class="col-sm-9">{{ $permission->guard_name }}</dd>
    <dt class="col-sm-3">Creado</dt>
    <dd class="col-sm-9">{{ $permission->created_at?->format('Y-m-d H:i') }}</dd>
</dl>
