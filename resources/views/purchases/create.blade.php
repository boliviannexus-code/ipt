@extends('layouts.admin')

@section('title', 'Nueva compra | Inventario POS')
@section('page-title', 'Nueva compra')
@section('page-subtitle', 'Ingreso de stock por presentaciones')

@section('content')
    <form
        class="purchase-form"
        method="POST"
        action="{{ route('purchases.store') }}"
        data-purchase-form
        data-reference-previews='@json($referencePreviews)'
        autocomplete="off"
        novalidate
    >
        @csrf

        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Datos principales</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6 col-xl-3">
                        <label class="form-label" for="supplier_id">Proveedor</label>
                        <select class="form-select @error('supplier_id') is-invalid @enderror" id="supplier_id" name="supplier_id" data-tom-select data-placeholder="Seleccionar proveedor">
                            <option value="">Sin proveedor</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>
                                    {{ $supplier->name }}{{ $supplier->company_name ? ' - '.$supplier->company_name : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <label class="form-label" for="warehouse_id">Almacen destino</label>
                        <select class="form-select @error('warehouse_id') is-invalid @enderror" id="warehouse_id" name="warehouse_id" data-tom-select data-placeholder="Seleccionar almacen" required>
                            <option value="">Seleccionar</option>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" @selected(old('warehouse_id') == $warehouse->id)>
                                    {{ $warehouse->branch?->name }} - {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('warehouse_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6 col-xl-2">
                        <label class="form-label" for="purchase_date">Fecha de compra</label>
                        <input class="form-control @error('purchase_date') is-invalid @enderror" id="purchase_date" name="purchase_date" type="date" value="{{ old('purchase_date', now()->toDateString()) }}" required>
                        @error('purchase_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6 col-xl-4">
                        <label class="form-label" for="reference_preview">Numero de comprobante</label>
                        <input class="form-control" id="reference_preview" data-reference-preview type="text" value="Se generara al seleccionar almacen" readonly>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="notes">Observaciones</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="2" placeholder="Notas internas de la compra">{{ old('notes') }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Detalle de productos</h3>
                <div class="card-actions">
                    <button class="btn btn-outline-primary btn-sm" type="button" data-add-purchase-item>
                        Agregar producto
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter purchase-items-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Presentacion</th>
                            <th class="text-end">Calculo unitario</th>
                            <th class="text-end">Cantidad</th>
                            <th class="text-end">Precio presentacion</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">Accion</th>
                        </tr>
                    </thead>
                    <tbody data-purchase-items>
                        @foreach (old('items', [[]]) as $index => $item)
                            @include('purchases.partials.item-row', ['index' => $index, 'item' => $item])
                        @endforeach
                    </tbody>
                </table>
            </div>
            @error('items')<div class="card-body pt-0"><div class="text-danger small">{{ $message }}</div></div>@enderror
        </div>

        <div class="purchase-summary">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <strong data-purchase-subtotal>0.00</strong>
                    </div>
                    <div class="d-flex justify-content-between fs-3">
                        <span>Total</span>
                        <strong data-purchase-total>0.00</strong>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end gap-2">
                    <a class="btn btn-outline-secondary" href="{{ route('purchases.index') }}">Cancelar</a>
                    <button class="btn btn-primary" type="submit">Guardar compra</button>
                </div>
            </div>
        </div>
    </form>

    <template data-purchase-item-template>
        @include('purchases.partials.item-row', ['index' => '__INDEX__', 'item' => []])
    </template>
@endsection
