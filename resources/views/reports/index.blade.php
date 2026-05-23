@extends('layouts.admin')

@section('title', $reportTitle.' | Reportes')
@section('page-title', 'Reportes')
@section('page-subtitle', 'Resumen y reportes independientes con filtros por periodo y almacen')

@section('content')
    <x-ui.table-card title="Filtros">
        <form class="row g-3 align-items-end" method="GET" action="{{ route('reports.index') }}" autocomplete="off">
            <div class="col-md-3">
                <label class="form-label" for="report-type">Reporte</label>
                <select class="form-select" id="report-type" name="type" data-tom-select>
                    @foreach ($reportTypes as $type => $label)
                        <option value="{{ $type }}" @selected($reportType === $type)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="report-from">Desde</label>
                <input class="form-control" id="report-from" name="from" type="date" value="{{ $filters['from']->toDateString() }}">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="report-to">Hasta</label>
                <input class="form-control" id="report-to" name="to" type="date" value="{{ $filters['to']->toDateString() }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="report-warehouse">Almacen</label>
                <select class="form-select" id="report-warehouse" name="warehouse_id" data-tom-select data-placeholder="Todos">
                    <option value="">Todos</option>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected((int) $filters['warehouse_id'] === $warehouse->id)>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary flex-fill" type="submit">
                    <i class="ti ti-filter"></i>
                    Filtrar
                </button>
                <a class="btn btn-outline-secondary" href="{{ route('reports.print', request()->query()) }}" target="_blank" rel="noopener" title="Imprimir o guardar como PDF">
                    <i class="ti ti-printer"></i>
                </a>
            </div>
        </form>
    </x-ui.table-card>

    <div class="d-flex flex-wrap gap-2 mt-3">
        @foreach ($reportTypes as $type => $label)
            <a class="btn btn-sm {{ $reportType === $type ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('reports.index', array_merge(request()->query(), ['type' => $type])) }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="d-flex align-items-center justify-content-between gap-3 mt-3">
        <div>
            <h2 class="h3 mb-1">{{ $reportTitle }}</h2>
            <div class="text-body-secondary">
                {{ $filters['from']->format('Y-m-d') }} al {{ $filters['to']->format('Y-m-d') }}
                · {{ $selectedWarehouse?->name ?? 'Todos los almacenes' }}
            </div>
        </div>
        <a class="btn btn-outline-primary" href="{{ route('reports.print', request()->query()) }}" target="_blank" rel="noopener">
            <i class="ti ti-file-type-pdf"></i>
            Imprimir PDF
        </a>
    </div>

    @include('reports.partials.sections')
@endsection
