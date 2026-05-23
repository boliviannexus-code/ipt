@extends('layouts.admin')

@php
    $maxTrend = max(1, collect($salesTrend)->max('total'));
    $maxTopProduct = max(1, $topProducts->max('total') ?? 1);
@endphp

@section('title', 'Dashboard | Inventario POS')
@section('page-title', 'Dashboard')
@section('page-subtitle', $dashboardCompany ? 'Resumen ejecutivo de '.$dashboardCompany->name : 'Resumen ejecutivo global de todas las empresas')

@section('content')
    <div class="dashboard-hero mb-3">
        <div class="dashboard-company">
            @if ($dashboardCompany?->logo_url)
                <img class="dashboard-company-logo" src="{{ $dashboardCompany->logo_url }}" alt="{{ $dashboardCompany->name }}">
            @else
                <span class="dashboard-company-mark">{{ str($dashboardCompany?->name ?? 'POS')->substr(0, 2)->upper() }}</span>
            @endif
            <div>
                <div class="text-body-secondary small">Empresa activa</div>
                <h2 class="mb-1">{{ $dashboardCompany?->name ?? 'Todas las empresas' }}</h2>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="badge text-bg-{{ $dashboardCompany ? 'primary' : 'purple' }}">
                        {{ $dashboardCompany ? 'Contexto de empresa' : 'Contexto global' }}
                    </span>
                    <span class="text-body-secondary small">{{ now()->format('Y-m-d H:i') }}</span>
                </div>
            </div>
        </div>
        <div class="dashboard-hero-actions">
            @can('pos.access')
                <a class="btn btn-primary" href="{{ route('pos.index') }}"><i class="ti ti-cash-register"></i> POS</a>
            @endcan
            @can('reports.view')
                <a class="btn btn-outline-primary" href="{{ route('reports.index') }}"><i class="ti ti-report-analytics"></i> Reportes</a>
            @endcan
        </div>
    </div>

    <div class="row g-3">
        <div class="col-sm-6 col-xl-3">
            <x-ui.stat-card label="Ventas hoy" :value="money_format_decimal($todaySalesTotal)" icon="ti ti-receipt" tone="primary" />
            <div class="dashboard-stat-note">{{ $todaySalesCount }} ventas realizadas</div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-ui.stat-card label="Ventas del mes" :value="money_format_decimal($monthSalesTotal)" icon="ti ti-chart-line" tone="success" />
            <div class="dashboard-stat-note">{{ $monthSalesCount }} ventas completadas</div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-ui.stat-card label="Compras del mes" :value="money_format_decimal($monthPurchasesTotal)" icon="ti ti-shopping-cart" tone="warning" />
            <div class="dashboard-stat-note">{{ $monthPurchasesCount }} compras registradas</div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-ui.stat-card label="Cajas abiertas" :value="$openCashRegisters" icon="ti ti-cash" tone="info" />
            <div class="dashboard-stat-note">Operacion activa en caja</div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-8">
            <x-ui.card title="Tendencia de ventas">
                <div class="card-body">
                    <div class="dashboard-bars" aria-label="Ventas de los ultimos 7 dias">
                        @foreach ($salesTrend as $point)
                            <div class="dashboard-bar-item">
                                <div class="dashboard-bar-value">{{ money_format_decimal($point['total']) }}</div>
                                <div class="dashboard-bar-track">
                                    <span style="height: {{ max(6, round(($point['total'] / $maxTrend) * 100)) }}%"></span>
                                </div>
                                <div class="dashboard-bar-label">{{ $point['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </x-ui.card>
        </div>
        <div class="col-lg-4">
            <x-ui.card title="Inventario">
                <div class="card-body">
                    <div class="dashboard-inventory-grid">
                        <div>
                            <div class="text-body-secondary small">Stock total</div>
                            <div class="h2 mb-0">{{ number_format($currentStockTotal) }}</div>
                        </div>
                        <div>
                            <div class="text-body-secondary small">Almacenes</div>
                            <div class="h2 mb-0">{{ $totalWarehouses }}</div>
                        </div>
                        <div>
                            <div class="text-body-secondary small">Productos</div>
                            <div class="h2 mb-0">{{ $totalProducts }}</div>
                        </div>
                        <div>
                            <div class="text-body-secondary small">Categorias</div>
                            <div class="h2 mb-0">{{ $totalCategories }}</div>
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-2 flex-wrap">
                        <span class="badge text-bg-success">{{ $activeProducts }} productos activos</span>
                        <span class="badge text-bg-info">{{ $activeCategories }} categorias activas</span>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-xl-5">
            <x-ui.card title="Productos mas vendidos">
                <div class="card-body">
                    @forelse ($topProducts as $product)
                        <div class="dashboard-rank-row">
                            <div class="dashboard-rank-main">
                                <div class="fw-semibold text-truncate">{{ $product->name }}</div>
                                <div class="text-body-secondary small">{{ number_format((float) $product->units) }} unidades</div>
                            </div>
                            <div class="dashboard-rank-meter"><span style="width: {{ round(((float) $product->total / $maxTopProduct) * 100) }}%"></span></div>
                            <div class="dashboard-rank-total">{{ money_format_decimal($product->total) }}</div>
                        </div>
                    @empty
                        <div class="text-center text-body-secondary py-4">Sin ventas completadas este mes.</div>
                    @endforelse
                </div>
            </x-ui.card>
        </div>
        <div class="col-xl-3">
            <x-ui.card title="Stock bajo">
                <div class="card-body">
                    @forelse ($lowStockProducts as $product)
                        <div class="dashboard-alert-row">
                            <span class="avatar avatar-sm bg-warning-lt text-warning"><i class="ti ti-alert-triangle"></i></span>
                            <div class="min-w-0">
                                <div class="fw-semibold text-truncate">{{ $product->name }}</div>
                                <div class="text-body-secondary small">{{ number_format((float) $product->stock) }} unidades disponibles</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-body-secondary py-4">Sin alertas de stock bajo.</div>
                    @endforelse
                </div>
            </x-ui.card>
        </div>
        <div class="col-xl-4">
            <x-ui.card title="Cajas abiertas">
                <div class="card-body">
                    @forelse ($openRegisters as $cashRegister)
                        <div class="dashboard-list-row">
                            <div>
                                <div class="fw-semibold">{{ $cashRegister->pointOfSale?->name ?? '-' }}</div>
                                <div class="text-body-secondary small">{{ $cashRegister->branch?->name ?? '-' }} · {{ $cashRegister->user?->name ?? '-' }}</div>
                            </div>
                            <span class="badge text-bg-success">{{ $cashRegister->opened_at?->diffForHumans() }}</span>
                        </div>
                    @empty
                        <div class="text-center text-body-secondary py-4">No hay cajas abiertas.</div>
                    @endforelse
                </div>
            </x-ui.card>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-6">
            <x-ui.table-card title="Ultimas ventas">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Comprobante</th>
                            <th>Almacen</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($latestSales as $sale)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $sale->receipt_number }}</div>
                                    <div class="text-body-secondary small">{{ $sale->sale_date?->format('Y-m-d H:i') }} · {{ $sale->user?->name }}</div>
                                </td>
                                <td>{{ $sale->warehouse?->name ?? '-' }}</td>
                                <td class="text-end fw-semibold">{{ money_format_decimal($sale->total) }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-body-secondary py-3" colspan="3">Sin ventas recientes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table-card>
        </div>
        <div class="col-lg-6">
            <x-ui.table-card title="Movimientos recientes">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Tipo</th>
                            <th class="text-end">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentMovements as $movement)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $movement->product?->name ?? '-' }}</div>
                                    <div class="text-body-secondary small">{{ $movement->warehouse?->name ?? '-' }} · {{ $movement->created_at?->format('Y-m-d H:i') }}</div>
                                </td>
                                <td><span class="badge text-bg-secondary">{{ str($movement->type?->value ?? $movement->type)->headline() }}</span></td>
                                <td class="text-end fw-semibold">{{ number_format((float) $movement->quantity) }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-body-secondary py-3" colspan="3">Sin movimientos recientes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table-card>
        </div>
    </div>
@endsection
