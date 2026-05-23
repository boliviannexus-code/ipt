@extends('layouts.admin')

@section('title', 'Proveedores | Inventario POS')
@section('page-title', 'Proveedores')
@section('page-subtitle', 'Listado administrativo de proveedores')

@section('content')
    <x-ui.table-card title="Listado de proveedores" data-refresh-container>
        <x-slot:actions>
            @can('suppliers.create')
                <a
                    class="btn btn-primary btn-sm"
                    href="{{ route('suppliers.create') }}"
                    data-modal-url="{{ route('suppliers.create') }}"
                    data-modal-title="Nuevo proveedor"
                >
                    Nuevo proveedor
                </a>
            @endcan
        </x-slot:actions>

        <table
            class="table table-hover align-middle"
            data-datatable
            data-url="{{ route('datatables.suppliers') }}"
            data-order='[[0,"desc"]]'
            data-columns-id="suppliers-table-columns"
        >
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Empresa</th>
                    <th>Email</th>
                    <th>Telefono</th>
                    <th>Estado</th>
                    <th>Creado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <script type="application/json" id="suppliers-table-columns">
            [
                {"data":"id","name":"suppliers.id"},
                {"data":"name","name":"suppliers.name"},
                {"data":"company_name","name":"suppliers.company_name","defaultContent":"-"},
                {"data":"email","name":"suppliers.email","defaultContent":"-"},
                {"data":"phone","name":"suppliers.phone","defaultContent":"-"},
                {"data":"is_active","name":"suppliers.is_active","orderable":false,"searchable":false},
                {"data":"created_at","name":"suppliers.created_at"},
                {"data":"actions","name":"actions","orderable":false,"searchable":false,"className":"text-end"}
            ]
        </script>
    </x-ui.table-card>
@endsection
