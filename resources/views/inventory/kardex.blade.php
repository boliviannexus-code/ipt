@extends('layouts.admin')

@section('title', 'Kardex | Inventario POS')
@section('page-title', 'Kardex')
@section('page-subtitle', 'Movimientos historicos de inventario')

@section('content')
    <x-ui.table-card title="Kardex de productos">
        <form class="stock-filter-bar" id="kardex-filters" autocomplete="off" data-datatable-filters>
            <div>
                <label class="form-label" for="kardex-filter-warehouse">Almacen</label>
                <select class="form-select form-select-sm" id="kardex-filter-warehouse" name="warehouse_id">
                    <option value="">Todos</option>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label" for="kardex-filter-category">Categoria</label>
                <select class="form-select form-select-sm" id="kardex-filter-category" name="category_id" data-tom-select data-placeholder="Todas">
                    <option value="">Todas</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label" for="kardex-filter-product">Producto</label>
                <select class="form-select form-select-sm" id="kardex-filter-product" name="product_id" data-tom-select data-placeholder="Todos">
                    <option value="">Todos</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label" for="kardex-filter-status">Estado</label>
                <select class="form-select form-select-sm" id="kardex-filter-status" name="status">
                    <option value="">Todos</option>
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>

            <button class="btn btn-outline-secondary btn-sm" type="reset">
                Limpiar
            </button>
        </form>

        <table
            class="table table-hover align-middle"
            data-datatable
            data-url="{{ route('datatables.kardex') }}"
            data-order='[[7,"desc"]]'
            data-columns-id="kardex-table-columns"
            data-filters-form="#kardex-filters"
        >
            <thead>
                <tr>
                    <th>Almacen</th>
                    <th>Sucursal</th>
                    <th>Producto</th>
                    <th>Categoria</th>
                    <th>Estado</th>
                    <th class="text-end">Entradas</th>
                    <th class="text-end">Salidas</th>
                    <th class="text-end">Saldo</th>
                    <th class="text-end">Mov.</th>
                    <th>Ultimo mov.</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <script type="application/json" id="kardex-table-columns">
            [
                {"data":"warehouse_name","name":"warehouses.name","defaultContent":"-"},
                {"data":"branch_name","name":"branches.name","defaultContent":"-"},
                {"data":"product_name","name":"products.name"},
                {"data":"category_name","name":"categories.name","defaultContent":"-"},
                {"data":"status","name":"products.is_active","orderable":false,"searchable":false},
                {"data":"entries","name":"entries","className":"text-end","searchable":false},
                {"data":"exits","name":"exits","className":"text-end","searchable":false},
                {"data":"balance","name":"balance","className":"text-end","searchable":false},
                {"data":"movements_count","name":"movements_count","className":"text-end","searchable":false},
                {"data":"last_movement_at","name":"last_movement_at","searchable":false},
                {"data":"actions","name":"actions","className":"text-end","orderable":false,"searchable":false}
            ]
        </script>
    </x-ui.table-card>
@endsection
