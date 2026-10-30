<dl class="row mb-0">
    <dt class="col-sm-4">Nombre</dt><dd class="col-sm-8">{{ $personnel->full_name }}</dd>
    <dt class="col-sm-4">CI</dt><dd class="col-sm-8">{{ $personnel->identity_document }}</dd>
    <dt class="col-sm-4">Nacimiento</dt><dd class="col-sm-8">{{ $personnel->birth_date?->format('d/m/Y') ?? 'No registrada' }}</dd>
    <dt class="col-sm-4">Contacto</dt><dd class="col-sm-8">{{ $personnel->phone ?: '—' }}<span class="d-block text-break">{{ $personnel->email ?: '—' }}</span></dd>
    <dt class="col-sm-4">Empresa</dt><dd class="col-sm-8">{{ $personnel->company->name }}</dd>
    <dt class="col-sm-4">Sede</dt><dd class="col-sm-8">{{ $personnel->campus ? $personnel->campus->name.' · '.$personnel->campus->code : 'Sin sede asignada' }}</dd>
    <dt class="col-sm-4">Área / Cargo</dt><dd class="col-sm-8">{{ $personnel->position->area->name }}<span class="d-block text-body-secondary">{{ $personnel->position->name }}</span></dd>
    <dt class="col-sm-4">Usuario</dt><dd class="col-sm-8">{{ $personnel->user?->email ?? 'Sin usuario asignado' }}</dd>
    <dt class="col-sm-4">Ventas</dt><dd class="col-sm-8"><span class="badge {{ $personnel->is_sales_enabled ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $personnel->is_sales_enabled ? 'Habilitado' : 'No habilitado' }}</span></dd>
</dl>
<div class="d-flex justify-content-end mt-4"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cerrar</button></div>
