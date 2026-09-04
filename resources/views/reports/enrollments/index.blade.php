@extends('layouts.admin')

@section('title', 'Reporte de matrículas | '.config('app.name'))
@section('page-title', 'Reporte de matrículas')
@section('page-subtitle', 'Inscripciones registradas por periodo y criterios comerciales')

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form id="enrollment-report-filters" method="GET" action="{{ route('reports.enrollments.index') }}" class="row g-3" role="search">
                <div class="col-sm-6 col-lg-3"><label class="form-label required" for="date_from">Desde</label><input class="form-control @error('date_from') is-invalid @enderror" id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] }}" required>@error('date_from')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-sm-6 col-lg-3"><label class="form-label required" for="date_to">Hasta</label><input class="form-control @error('date_to') is-invalid @enderror" id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] }}" required>@error('date_to')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-sm-6 col-lg-3"><label class="form-label" for="campus_id">Sede</label><select class="form-select" id="campus_id" name="campus_id"><option value="">Todas</option>@foreach($campuses as $campus)<option value="{{ $campus->id }}" @selected((int)($filters['campus_id'] ?? 0)===$campus->id)>{{ $campus->name }} · {{ $campus->code }}</option>@endforeach</select></div>
                <div class="col-sm-6 col-lg-3"><label class="form-label" for="program_id">Programa</label><select class="form-select" id="program_id" name="program_id"><option value="">Todos</option>@foreach($programs as $program)<option value="{{ $program->id }}" @selected((int)($filters['program_id'] ?? 0)===$program->id)>{{ $program->title }}</option>@endforeach</select></div>
                <div class="col-sm-6 col-lg-3"><label class="form-label" for="plan_id">Plan</label><select class="form-select" id="plan_id" name="plan_id"><option value="">Todos</option>@foreach($plans as $plan)<option value="{{ $plan->id }}" @selected((int)($filters['plan_id'] ?? 0)===$plan->id)>{{ $plan->name }}</option>@endforeach</select></div>
                <div class="col-sm-6 col-lg-3"><label class="form-label" for="sales_executive_id">Ejecutivo de ventas</label><select class="form-select" id="sales_executive_id" name="sales_executive_id"><option value="">Todos</option>@foreach($salesExecutives as $executive)<option value="{{ $executive->id }}" @selected((int)($filters['sales_executive_id'] ?? 0)===$executive->id)>{{ $executive->full_name }}</option>@endforeach</select></div>
                <div class="col-sm-6 col-lg-3"><label class="form-label" for="commercial_origin_id">Origen comercial</label><select class="form-select" id="commercial_origin_id" name="commercial_origin_id"><option value="">Todos</option>@foreach($commercialOrigins as $origin)<option value="{{ $origin->id }}" @selected((int)($filters['commercial_origin_id'] ?? 0)===$origin->id)>{{ $origin->name }}</option>@endforeach</select></div>
                <div class="col-sm-6 col-lg-3"><label class="form-label" for="status">Estado</label><select class="form-select" id="status" name="status"><option value="">Todos</option><option value="completed" @selected(($filters['status'] ?? '')==='completed')>Completada</option><option value="draft" @selected(($filters['status'] ?? '')==='draft')>En proceso</option></select></div>
                <div class="col-sm-6 col-lg-3"><label class="form-label" for="payment_method_code">Método de pago</label><select class="form-select" id="payment_method_code" name="payment_method_code"><option value="">Todos</option>@foreach($paymentMethods as $method)<option value="{{ $method['code'] }}" @selected((int)($filters['payment_method_code'] ?? 0)===$method['code'])>{{ $method['label'] }}</option>@endforeach</select></div>
                <div class="col-12 d-flex flex-wrap justify-content-end align-items-end gap-2"><a class="btn btn-outline-secondary" href="{{ route('reports.enrollments.index') }}">Limpiar</a><button class="btn btn-primary" type="submit"><i class="ti ti-filter me-1" aria-hidden="true"></i>Aplicar filtros</button><a class="btn btn-outline-success" href="{{ route('reports.enrollments.excel', request()->query()) }}"><i class="ti ti-file-spreadsheet me-1" aria-hidden="true"></i>Exportar a Excel</a><a class="btn btn-success" href="{{ route('reports.enrollments.pdf', request()->query()) }}" target="_blank" rel="noopener"><i class="ti ti-printer me-1" aria-hidden="true"></i>Imprimir</a></div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        @foreach([['Total',$summary['total'],'primary'],['Completadas',$summary['completed'],'success'],['En proceso',$summary['draft'],'azure'],['Sedes',$summary['campuses'],'secondary']] as [$label,$value,$color])
            <div class="col-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-secondary small">{{ $label }}</div><div class="display-6 fw-semibold text-{{ $color }}">{{ $value }}</div></div></div></div>
        @endforeach
    </div>

    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title mb-0">Resumen económico</h3></div>
        <div class="card-body"><div class="row g-3">
            <div class="col-md-4"><div class="text-secondary small">Cargos generados</div><div class="h2 mb-0">Bs {{ number_format($summary['charged'], 2, ',', '.') }}</div></div>
            <div class="col-md-4"><div class="text-secondary small">Recaudación efectiva</div><div class="h2 mb-0 text-success">Bs {{ number_format($summary['collected'], 2, ',', '.') }}</div></div>
            <div class="col-md-4"><div class="text-secondary small">Saldo pendiente</div><div class="h2 mb-0 {{ $summary['balance'] > 0 ? 'text-danger' : 'text-success' }}">Bs {{ number_format($summary['balance'], 2, ',', '.') }}</div></div>
        </div></div>
    </div>

    <x-ui.table-card title="Resultados">
        <table class="table table-hover align-middle mb-0" data-datatable data-url="{{ route('reports.enrollments.data') }}" data-columns-id="enrollment-report-columns" data-filters-form="#enrollment-report-filters" data-page-length="25" data-order='[[0,"desc"]]'><thead><tr><th>Fecha</th><th>Matrícula / Sede</th><th>Estudiante</th><th>Programa / Plan</th><th>Ejecutivo</th><th>Origen</th><th>Pago</th><th class="text-end">Cargos</th><th class="text-end">Recaudado</th><th class="text-end">Saldo</th><th>Estado</th></tr></thead></table>
        <script type="application/json" id="enrollment-report-columns">[{"data":"created_at","name":"created_at"},{"data":"enrollment","orderable":false},{"data":"student_name","orderable":false},{"data":"program_plan","orderable":false},{"data":"executive","orderable":false},{"data":"origin","orderable":false},{"data":"payment","orderable":false,"searchable":false},{"data":"charged","orderable":false,"searchable":false},{"data":"collected","orderable":false,"searchable":false},{"data":"balance","orderable":false,"searchable":false},{"data":"status","name":"status"}]</script>
    </x-ui.table-card>
@endsection
