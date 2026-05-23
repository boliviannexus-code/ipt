@extends('layouts.admin')

@section('title', 'Compra '.$purchase->reference.' | Inventario POS')
@section('page-title', 'Compra '.$purchase->reference)
@section('page-subtitle', 'Detalle de ingreso de stock')

@section('content')
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">Datos principales</h3>
            @can('purchases.void')
                @if ($purchase->status !== 'voided')
                    <div class="card-actions">
                        <form method="POST" action="{{ route('purchases.void', $purchase) }}" data-confirm-void-purchase data-refresh-url="{{ route('purchases.show', $purchase) }}">
                            @csrf
                            <button class="btn btn-outline-danger btn-sm" type="submit">Anular compra</button>
                        </form>
                    </div>
                @endif
            @endcan
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Proveedor</dt>
                <dd class="col-sm-9">{{ $purchase->supplier?->name ?? 'Sin proveedor' }}</dd>
                <dt class="col-sm-3">Almacen destino</dt>
                <dd class="col-sm-9">{{ $purchase->warehouse?->branch?->name }} - {{ $purchase->warehouse?->name }}</dd>
                <dt class="col-sm-3">Fecha</dt>
                <dd class="col-sm-9">{{ $purchase->purchase_date?->format('Y-m-d') }}</dd>
                <dt class="col-sm-3">Estado</dt>
                <dd class="col-sm-9">
                    @if ($purchase->status === 'voided')
                        <span class="badge text-bg-danger">Anulada</span>
                    @else
                        <span class="badge text-bg-success">Completada</span>
                    @endif
                </dd>
                <dt class="col-sm-3">Observaciones</dt>
                <dd class="col-sm-9">{{ $purchase->notes ?: '-' }}</dd>
            </dl>
        </div>
    </div>

    <x-ui.table-card title="Detalle de productos">
        <table class="table table-vcenter">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Presentacion</th>
                    <th class="text-end">Cantidad</th>
                    <th class="text-end">Unidades</th>
                    <th class="text-end">Precio presentacion</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($purchase->details as $detail)
                    <tr>
                        <td>{{ $detail->product?->name }}</td>
                        <td>{{ $detail->presentation_name }} x {{ $detail->units_per_package }}</td>
                        <td class="text-end">{{ $detail->package_quantity }}</td>
                        <td class="text-end">{{ $detail->quantity }} {{ $detail->product?->measurementUnit?->abbreviation }}</td>
                        <td class="text-end">{{ money_format_decimal($detail->unit_price) }}</td>
                        <td class="text-end">{{ money_format_decimal($detail->subtotal) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <x-slot:footer>
            <div class="d-flex justify-content-between align-items-center w-100">
                <a class="btn btn-outline-secondary" href="{{ route('purchases.index') }}">Volver</a>
                <div class="text-end">
                    <div>Subtotal: <strong>{{ money_format_decimal($purchase->subtotal) }}</strong></div>
                    <div class="fs-3">Total: <strong>{{ money_format_decimal($purchase->total) }}</strong></div>
                </div>
            </div>
        </x-slot:footer>
    </x-ui.table-card>
@endsection
