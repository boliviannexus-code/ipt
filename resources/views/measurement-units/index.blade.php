@extends('layouts.admin')

@section('title', 'Unidades de medida | Inventario POS')
@section('page-title', 'Unidades de medida')
@section('page-subtitle', 'Catalogo de unidades para productos e inventario')

@section('content')
    <x-ui.table-card title="Listado de unidades" data-refresh-container>
        <x-slot:actions>
            @can('measurement-units.create')
                <a
                    class="btn btn-primary btn-sm"
                    href="{{ route('measurement-units.create') }}"
                    data-modal-url="{{ route('measurement-units.create') }}"
                    data-modal-title="Nueva unidad de medida"
                >
                    Nueva unidad
                </a>
            @endcan
        </x-slot:actions>

        <table
            class="table table-hover align-middle"
            data-datatable
            data-url="{{ route('datatables.measurement-units') }}"
            data-order='[[0,"desc"]]'
            data-columns-id="measurement-units-table-columns"
        >
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Abreviatura</th>
                    <th>Estado</th>
                    <th>Creado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <script type="application/json" id="measurement-units-table-columns">
            [
                {"data":"id","name":"measurement_units.id"},
                {"data":"name","name":"measurement_units.name"},
                {"data":"abbreviation","name":"measurement_units.abbreviation"},
                {"data":"is_active","name":"measurement_units.is_active","orderable":false,"searchable":false},
                {"data":"created_at","name":"measurement_units.created_at"},
                {"data":"actions","name":"actions","orderable":false,"searchable":false,"className":"text-end"}
            ]
        </script>
    </x-ui.table-card>
@endsection
