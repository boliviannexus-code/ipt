@extends('layouts.admin')

@section('title', 'Presentaciones | Inventario POS')
@section('page-title', 'Presentaciones')
@section('page-subtitle', 'Empaques universales reutilizables por varios productos')

@section('content')
    <x-ui.table-card title="Listado de presentaciones" data-refresh-container>
        <x-slot:actions>
            @can('product-presentations.create')
                <a class="btn btn-primary btn-sm" href="{{ route('product-presentations.create') }}" data-modal-url="{{ route('product-presentations.create') }}" data-modal-title="Nueva presentacion">
                    Nueva presentacion
                </a>
            @endcan
        </x-slot:actions>

        <table class="table table-hover align-middle" data-datatable data-url="{{ route('datatables.product-presentations') }}" data-order='[[0,"desc"]]' data-columns-id="product-presentations-table-columns">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Presentacion</th>
                    <th>Factor</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <script type="application/json" id="product-presentations-table-columns">
            [
                {"data":"id","name":"presentations.id"},
                {"data":"name","name":"presentations.name"},
                {"data":"factor","name":"factor","orderable":false,"searchable":false},
                {"data":"is_active","name":"presentations.is_active","orderable":false,"searchable":false},
                {"data":"actions","name":"actions","orderable":false,"searchable":false,"className":"text-end"}
            ]
        </script>
    </x-ui.table-card>
@endsection
