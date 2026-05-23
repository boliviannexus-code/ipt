@extends('layouts.admin')

@section('title', 'Stock | Inventario POS')
@section('page-title', 'Stock por almacen')
@section('page-subtitle', 'Existencias y movimientos de inventario')

@section('content')
    <x-ui.table-card title="Existencias por almacen" data-refresh-container>
        @can('inventory.movements')
            <x-slot:actions>
                <a class="btn btn-primary btn-sm" href="{{ route('inventory.transfers.create') }}" data-modal-url="{{ route('inventory.transfers.create') }}" data-modal-title="Transferir entre almacenes">
                    Transferir
                </a>
            </x-slot:actions>
        @endcan

        <form class="stock-filter-bar" id="stock-filters" autocomplete="off" data-datatable-filters>
            <div>
                <label class="form-label" for="stock-filter-warehouse">Almacen</label>
                <select class="form-select form-select-sm" id="stock-filter-warehouse" name="warehouse_id">
                    <option value="">Todos</option>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label" for="stock-filter-category">Categoria</label>
                <select class="form-select form-select-sm" id="stock-filter-category" name="category_id" data-tom-select data-placeholder="Todas">
                    <option value="">Todas</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label" for="stock-filter-product">Producto</label>
                <select class="form-select form-select-sm" id="stock-filter-product" name="product_id" data-tom-select data-placeholder="Todos">
                    <option value="">Todos</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label" for="stock-filter-status">Estado</label>
                <select class="form-select form-select-sm" id="stock-filter-status" name="status">
                    <option value="">Todos</option>
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>

            <label class="form-check form-switch stock-filter-toggle" for="stock-filter-low-stock">
                <input class="form-check-input" id="stock-filter-low-stock" name="low_stock" type="checkbox" value="1">
                <span class="form-check-label">Bajo stock</span>
            </label>

            <button class="btn btn-outline-secondary btn-sm" type="reset">
                Limpiar
            </button>
        </form>

        <table
            class="table table-hover align-middle"
            data-datatable
            data-url="{{ route('datatables.stock') }}"
            data-order='[[0,"asc"]]'
            data-columns-id="stock-table-columns"
            data-filters-form="#stock-filters"
        >
            <thead>
                <tr>
                    <th>Almacen</th>
                    <th>Sucursal</th>
                    <th>Producto</th>
                    <th>Categoria</th>
                    <th>Presentaciones</th>
                    <th>Estado</th>
                    <th class="text-end">Stock</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <script type="application/json" id="stock-table-columns">
            [
                {"data":"warehouse_name","name":"warehouses.name"},
                {"data":"branch_name","name":"branches.name","defaultContent":"-"},
                {"data":"product_name","name":"products.name"},
                {"data":"category_name","name":"categories.name","defaultContent":"-"},
                {"data":"presentations","name":"presentations","orderable":false,"searchable":false},
                {"data":"status","name":"products.is_active","orderable":false,"searchable":false},
                {"data":"stock","name":"stock","className":"text-end","searchable":false},
                {"data":"actions","name":"actions","className":"text-end","orderable":false,"searchable":false}
            ]
        </script>
    </x-ui.table-card>
@endsection
