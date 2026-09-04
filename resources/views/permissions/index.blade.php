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
        <table class="table table-hover align-middle" data-datatable data-url="{{ route('datatables.permissions') }}" data-columns-id="permissions-columns" data-order='[[0,"asc"]]'>
            <thead><tr><th>Permiso</th><th>Guard</th><th>Creado</th><th class="text-end">Acciones</th></tr></thead>
        </table>
        <script type="application/json" id="permissions-columns">[{"data":"name","name":"name"},{"data":"guard_name","name":"guard_name"},{"data":"created_at","name":"created_at"},{"data":"actions","orderable":false,"searchable":false}]</script>
    </x-ui.table-card>
@endsection
