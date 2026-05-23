@extends('layouts.admin')

@section('title', 'Permisos | '.config('app.name', 'Base Admin'))
@section('page-title', 'Permisos')
@section('page-subtitle', 'Administracion de capacidades del sistema')

@section('content')
    <x-ui.table-card title="Listado de permisos" data-refresh-container>
        <x-slot:actions>
            @can('permissions.create')
                <a class="btn btn-primary btn-sm" href="{{ route('permissions.create') }}" data-modal-url="{{ route('permissions.create') }}" data-modal-title="Nuevo permiso">Nuevo permiso</a>
            @endcan
        </x-slot:actions>
        <table class="table table-hover align-middle">
            <thead><tr><th>Permiso</th><th>Guard</th><th>Creado</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
                @forelse ($permissions as $permission)
                    <tr>
                        <td>{{ permission_label($permission->name) }}</td>
                        <td>{{ $permission->guard_name }}</td>
                        <td>{{ $permission->created_at?->format('Y-m-d') }}</td>
                        <td class="text-end">
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('permissions.show', $permission) }}" data-modal-url="{{ route('permissions.show', $permission) }}" data-modal-title="Detalle de permiso">Ver</a>
                            @can('permissions.edit')
                                <a class="btn btn-outline-primary btn-sm" href="{{ route('permissions.edit', $permission) }}" data-modal-url="{{ route('permissions.edit', $permission) }}" data-modal-title="Editar permiso">Editar</a>
                            @endcan
                            @can('permissions.delete')
                                <form class="d-inline" method="POST" action="{{ route('permissions.destroy', $permission) }}" data-confirm-delete="Eliminar permiso?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" type="submit">Eliminar</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="4" message="No hay permisos registrados." />
                @endforelse
            </tbody>
        </table>
        <x-slot:footer>{{ $permissions->links() }}</x-slot:footer>
    </x-ui.table-card>
@endsection
