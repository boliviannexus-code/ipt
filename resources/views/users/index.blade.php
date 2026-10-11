@extends('layouts.admin')
@section('title', 'Usuarios | '.config('app.name', 'Base Admin'))
@section('page-title', 'Usuarios')
@section('page-subtitle', 'Administracion de accesos, roles y estado')
@section('content')
<x-ui.table-card title="Listado de usuarios" data-refresh-container>
    <x-slot:actions>@can('users.create')<a class="btn btn-primary btn-sm" href="{{ route('users.create') }}" data-modal-url="{{ route('users.create') }}" data-modal-title="Nuevo usuario">Nuevo usuario</a>@endcan</x-slot:actions>
    <table class="table table-hover align-middle" data-datatable data-url="{{ route('datatables.users') }}" data-columns-id="users-columns" data-order='[[5,"desc"]]'>
        <thead><tr><th>Personal / Usuario</th><th>Email</th><th>Empresa</th><th>Roles</th><th>Estado</th><th>Creado</th><th class="text-end">Acciones</th></tr></thead>
    </table>
    <script type="application/json" id="users-columns">[{"data":"display_name","name":"display_name"},{"data":"email","name":"email"},{"data":"company_name","name":"company_name"},{"data":"roles_list","orderable":false,"searchable":false},{"data":"status","name":"is_active"},{"data":"created_at","name":"created_at"},{"data":"actions","orderable":false,"searchable":false}]</script>
</x-ui.table-card>
@endsection
