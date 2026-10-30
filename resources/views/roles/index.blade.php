@extends('layouts.admin')

@section('title', 'Roles | '.config('app.name', 'Base Admin'))
@section('page-title', 'Roles')
@section('page-subtitle', 'Administracion de perfiles y permisos')

@section('content')
    <x-ui.table-card title="Listado de roles" data-refresh-container>
        <x-slot:actions>
            @can('roles.create')
                <a class="btn btn-primary btn-sm" href="{{ route('roles.create') }}" data-modal-url="{{ route('roles.create') }}" data-modal-title="Nuevo rol" data-modal-size="xl">Nuevo rol</a>
            @endcan
        </x-slot:actions>
        <table class="table table-hover align-middle" data-datatable data-url="{{ route('datatables.roles') }}" data-columns-id="roles-columns" data-order='[[0,"asc"]]'>
            <thead><tr><th>Rol</th><th>Usuarios</th><th>Permisos</th><th>Creado</th><th class="text-end">Acciones</th></tr></thead>
        </table>
        <script type="application/json" id="roles-columns">[{"data":"name","name":"name"},{"data":"users_count","searchable":false},{"data":"permissions_count","searchable":false},{"data":"created_at","name":"created_at"},{"data":"actions","orderable":false,"searchable":false}]</script>
    </x-ui.table-card>
@endsection
