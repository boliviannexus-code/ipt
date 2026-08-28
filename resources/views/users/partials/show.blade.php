<dl class="row mb-0">
    <dt class="col-sm-3">Nombre</dt>
    <dd class="col-sm-9">{{ $user->name }}</dd>

    <dt class="col-sm-3">Personal</dt>
    <dd class="col-sm-9">{{ $user->personnel?->full_name ?? 'Sin personal vinculado' }} @if($user->personnel)<span class="text-body-secondary">· {{ $user->personnel->position->area->name }} / {{ $user->personnel->position->name }}</span>@endif</dd>

    <dt class="col-sm-3">Email</dt>
    <dd class="col-sm-9">{{ $user->email }}</dd>

    <dt class="col-sm-3">Empresa</dt>
    <dd class="col-sm-9">{{ $user->company?->name ?? 'Sin empresa' }}</dd>

    <dt class="col-sm-3">Roles</dt>
    <dd class="col-sm-9">
        @forelse ($user->roles as $role)
            <span class="badge text-bg-primary">{{ $role->name }}</span>
        @empty
            <span class="text-body-secondary">Sin roles</span>
        @endforelse
    </dd>

    <dt class="col-sm-3">Estado</dt>
    <dd class="col-sm-9">
        <span class="badge text-bg-{{ $user->is_active ? 'success' : 'secondary' }}">{{ $user->is_active ? 'Activo' : 'Inactivo' }}</span>
        @if ($user->trashed())
            <span class="badge text-bg-danger">Eliminado</span>
        @endif
    </dd>

    <dt class="col-sm-3">Creado</dt>
    <dd class="col-sm-9">{{ $user->created_at?->format('Y-m-d H:i') }}</dd>
</dl>
