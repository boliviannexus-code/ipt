@extends('layouts.admin')

@section('title', 'Productos | Inventario POS')
@section('page-title', 'Productos')
@section('page-subtitle', 'Catalogo comercial para inventario y POS')

@section('content')
    <x-ui.table-card title="Listado de productos" data-refresh-container>
        <x-slot:actions>
            @can('products.create')
                <a
                    class="btn btn-primary btn-sm"
                    href="{{ route('products.create') }}"
                    data-modal-url="{{ route('products.create') }}"
                    data-modal-title="Nuevo producto"
                >
                    Nuevo producto
                </a>
            @endcan
        </x-slot:actions>

        <table
            class="table table-hover align-middle"
            data-datatable
            data-url="{{ route('datatables.products') }}"
            data-order='[[0,"desc"]]'
            data-columns-id="products-table-columns"
        >
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Imagen</th>
                    <th>Producto</th>
                    <th>Categoria</th>
                    <th>Unidad</th>
                    <th>Barcode</th>
                    <th>Compra</th>
                    <th>Venta</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <script type="application/json" id="products-table-columns">
            [
                {"data":"id","name":"products.id"},
                {"data":"image","name":"image","orderable":false,"searchable":false},
                {"data":"name","name":"products.name"},
                {"data":"category_name","name":"categories.name","defaultContent":"-"},
                {"data":"measurement_unit_abbreviation","name":"measurement_units.abbreviation","defaultContent":"-"},
                {"data":"barcode","name":"products.barcode","defaultContent":"-"},
                {"data":"purchase_price","name":"products.purchase_price","className":"text-end"},
                {"data":"sale_price","name":"products.sale_price","className":"text-end"},
                {"data":"is_active","name":"products.is_active","orderable":false,"searchable":false},
                {"data":"actions","name":"actions","orderable":false,"searchable":false,"className":"text-end"}
            ]
        </script>
    </x-ui.table-card>
@endsection
