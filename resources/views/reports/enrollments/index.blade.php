@extends('layouts.admin')

@section('title', 'Reporte de matrículas | '.config('app.name'))
@section('page-title', 'Reporte de matrículas')
@section('page-subtitle', 'Inscripciones registradas por periodo y criterios comerciales')

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.enrollments.index') }}" class="row g-3" role="search">
                <div class="col-sm-6 col-lg-3"><label class="form-label required" for="date_from">Desde</label><input class="form-control @error('date_from') is-invalid @enderror" id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] }}" required>@error('date_from')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-sm-6 col-lg-3"><label class="form-label required" for="date_to">Hasta</label><input class="form-control @error('date_to') is-invalid @enderror" id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] }}" required>@error('date_to')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-sm-6 col-lg-3"><label class="form-label" for="campus_id">Sede</label><select class="form-select" id="campus_id" name="campus_id"><option value="">Todas</option>@foreach($campuses as $campus)<option value="{{ $campus->id }}" @selected((int)($filters['campus_id'] ?? 0)===$campus->id)>{{ $campus->name }} · {{ $campus->code }}</option>@endforeach</select></div>
                <div class="col-sm-6 col-lg-3"><label class="form-label" for="program_id">Programa</label><select class="form-select" id="program_id" name="program_id"><option value="">Todos</option>@foreach($programs as $program)<option value="{{ $program->id }}" @selected((int)($filters['program_id'] ?? 0)===$program->id)>{{ $program->title }}</option>@endforeach</select></div>
                <div class="col-sm-6 col-lg-3"><label class="form-label" for="plan_id">Plan</label><select class="form-select" id="plan_id" name="plan_id"><option value="">Todos</option>@foreach($plans as $plan)<option value="{{ $plan->id }}" @selected((int)($filters['plan_id'] ?? 0)===$plan->id)>{{ $plan->name }}</option>@endforeach</select></div>
                <div class="col-sm-6 col-lg-3"><label class="form-label" for="sales_executive_id">Ejecutivo de ventas</label><select class="form-select" id="sales_executive_id" name="sales_executive_id"><option value="">Todos</option>@foreach($salesExecutives as $executive)<option value="{{ $executive->id }}" @selected((int)($filters['sales_executive_id'] ?? 0)===$executive->id)>{{ $executive->full_name }}</option>@endforeach</select></div>
                <div class="col-sm-6 col-lg-3"><label class="form-label" for="commercial_origin_id">Origen comercial</label><select class="form-select" id="commercial_origin_id" name="commercial_origin_id"><option value="">Todos</option>@foreach($commercialOrigins as $origin)<option value="{{ $origin->id }}" @selected((int)($filters['commercial_origin_id'] ?? 0)===$origin->id)>{{ $origin->name }}</option>@endforeach</select></div>
                <div class="col-sm-6 col-lg-3"><label class="form-label" for="status">Estado</label><select class="form-select" id="status" name="status"><option value="">Todos</option><option value="completed" @selected(($filters['status'] ?? '')==='completed')>Completada</option><option value="draft" @selected(($filters['status'] ?? '')==='draft')>En proceso</option></select></div>
                <div class="col-12 d-flex flex-wrap justify-content-end align-items-end gap-2"><a class="btn btn-outline-secondary" href="{{ route('reports.enrollments.index') }}">Limpiar</a><button class="btn btn-primary" type="submit"><i class="ti ti-filter me-1" aria-hidden="true"></i>Aplicar filtros</button><a class="btn btn-success" href="{{ route('reports.enrollments.pdf', request()->query()) }}" target="_blank" rel="noopener"><i class="ti ti-printer me-1" aria-hidden="true"></i>Imprimir</a></div>
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
        <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Fecha</th><th>Matrícula / Sede</th><th>Estudiante</th><th>Programa / Plan</th><th>Ejecutivo</th><th>Origen</th><th class="text-end">Cargos</th><th class="text-end">Recaudado</th><th class="text-end">Saldo</th><th>Estado</th></tr></thead><tbody>
            @forelse($applications as $application)@php($charged = (float)($application->contract?->charges->sum('amount') ?? 0))@php($collected = (float)($application->contract?->charges->sum('paid_amount') ?? 0))<tr><td>{{ $application->created_at->format('d/m/Y') }}</td><td><span class="fw-semibold d-block">{{ $application->account_number ?: 'Pendiente' }}</span><small class="text-secondary">{{ $application->campus?->name ?? 'Sin sede' }}</small></td><td><span class="d-block">{{ trim("{$application->student_first_name} {$application->student_paternal_surname} {$application->student_maternal_surname}") ?: trim("{$application->first_name} {$application->paternal_surname}") }}</span><small class="text-secondary">CI {{ $application->student_identity_document ?: $application->identity_document }}</small></td><td><span class="d-block">{{ $application->program?->title ?? 'Pendiente' }}</span><small class="text-secondary">{{ $application->plan?->name }}</small></td><td>{{ $application->salesExecutive?->full_name ?? 'Pendiente' }}</td><td>{{ $application->commercialOrigin?->name ?? 'Pendiente' }}</td><td class="text-end">Bs {{ number_format($charged, 2, ',', '.') }}</td><td class="text-end text-success">Bs {{ number_format($collected, 2, ',', '.') }}</td><td class="text-end {{ $charged-$collected > 0 ? 'text-danger' : '' }}">Bs {{ number_format($charged-$collected, 2, ',', '.') }}</td><td><span class="badge {{ $application->status==='completed' ? 'text-bg-success' : 'text-bg-azure' }}">{{ $application->status==='completed' ? 'Completada' : 'En proceso' }}</span></td></tr>
            @empty<x-ui.empty-row colspan="10" message="No existen matrículas para los filtros seleccionados." />@endforelse
        </tbody></table></div><x-slot:footer>{{ $applications->links() }}</x-slot:footer>
    </x-ui.table-card>
@endsection
