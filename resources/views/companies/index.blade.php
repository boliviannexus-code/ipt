@extends('layouts.admin')

@section('title', 'Empresas | '.config('app.name', 'Base Admin'))
@section('page-title', 'Empresas')
@section('page-subtitle', 'Datos base para reportes y asignacion de usuarios')

@section('content')
    <x-ui.table-card title="Listado de empresas" data-refresh-container>
        <x-slot:actions>
            @can('companies.create')
                <a class="btn btn-primary btn-sm" href="{{ route('companies.create') }}" data-modal-url="{{ route('companies.create') }}" data-modal-title="Nueva empresa">Nueva empresa</a>
            @endcan
        </x-slot:actions>

        <table class="table table-hover align-middle" data-datatable data-url="{{ route('datatables.companies') }}" data-columns-id="companies-columns" data-order='[[1,"asc"]]'>
            <thead>
                <tr>
                    <th>Logo</th>
                    <th>Nombre</th>
                    <th>NIT/Documento</th>
                    <th>Contacto</th>
                    <th>Usuarios</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
        </table>
        <script type="application/json" id="companies-columns">[{"data":"logo","orderable":false,"searchable":false},{"data":"display_name","name":"name"},{"data":"tax_id","name":"tax_id"},{"data":"contact","name":"email"},{"data":"users_count","searchable":false},{"data":"is_active","name":"is_active"},{"data":"actions","orderable":false,"searchable":false}]</script>
    </x-ui.table-card>
@endsection
