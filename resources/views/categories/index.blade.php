@extends('layouts.admin')

@section('title', 'Categorias | Inventario POS')
@section('page-title', 'Categorias')
@section('page-subtitle', 'Administracion del catalogo de categorias')

@section('content')
    <x-ui.table-card title="Listado de categorias" data-refresh-container>
        <x-slot:actions>
            @can('categories.create')
                <a
                    class="btn btn-primary btn-sm"
                    href="{{ route('categories.create') }}"
                    data-modal-url="{{ route('categories.create') }}"
                    data-modal-title="Nueva categoria"
                >
                    Nueva categoria
                </a>
            @endcan
        </x-slot:actions>

        <table
            class="table table-hover align-middle"
            data-datatable
            data-url="{{ route('datatables.categories') }}"
            data-order='[[0,"desc"]]'
            data-columns-id="categories-table-columns"
        >
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripcion</th>
                    <th>Estado</th>
                    <th>Creado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <script type="application/json" id="categories-table-columns">
            [
                {"data":"id","name":"categories.id"},
                {"data":"name","name":"categories.name"},
                {"data":"description","name":"categories.description","defaultContent":"-"},
                {"data":"is_active","name":"categories.is_active","orderable":false,"searchable":false},
                {"data":"created_at","name":"categories.created_at"},
                {"data":"actions","name":"actions","orderable":false,"searchable":false,"className":"text-end"}
            ]
        </script>
    </x-ui.table-card>
@endsection
