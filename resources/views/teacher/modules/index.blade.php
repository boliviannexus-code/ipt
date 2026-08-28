@extends('layouts.admin')
@section('title', 'Mis módulos')
@section('page-title', 'Mis módulos')
@section('page-subtitle', 'Clases y asistencia de los módulos asignados')
@section('content')
<div class="row g-3">
    @forelse($modules as $module)
        @php($todaySession = $module->classSessions->first())
        @php($isCurrent = today()->betweenIncluded($module->start_date, $module->end_date))
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div><h3 class="card-title mb-1">{{ $module->name }}</h3><div class="text-secondary">{{ $module->program->title }} · {{ $module->level->name }}</div></div>
                        <span class="badge {{ $module->modality === 'virtual' ? 'text-bg-azure' : 'text-bg-green' }}">{{ $module->modality === 'virtual' ? 'Virtual' : 'Presencial' }}</span>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-4"><span class="text-secondary small d-block">Horario</span><strong>{{ substr($module->starts_at, 0, 5) }}–{{ substr($module->ends_at, 0, 5) }}</strong></div>
                        <div class="col-4"><span class="text-secondary small d-block">Vigencia</span><strong>{{ $module->start_date->format('d/m/Y') }}–{{ $module->end_date->format('d/m/Y') }}</strong></div>
                        <div class="col-4"><span class="text-secondary small d-block">Estudiantes</span><strong>{{ $module->student_assignments_count ?? $module->studentAssignments->count() }}</strong></div>
                    </div>
                    @if($todaySession)<div class="alert alert-success py-2 mb-0"><i class="ti ti-circle-check me-1"></i>Clase iniciada hoy a las {{ $todaySession->started_at->format('H:i') }} · {{ $todaySession->attendances_count }} asistencias registradas</div>@elseif(!$isCurrent)<div class="alert alert-secondary py-2 mb-0">{{ today()->lt($module->start_date) ? 'El módulo aún no inició.' : 'El módulo finalizó.' }}</div>@endif
                </div>
                <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
                    @if(today()->gte($module->end_date))
                        <a class="btn btn-outline-primary" href="{{ route('teacher.modules.results.edit', $module) }}"><i class="ti ti-rosette-discount-check me-1"></i>{{ $module->studentResults->isEmpty() ? 'Cerrar módulo' : 'Ver resultados' }}</a>
                    @endif
                    @if($todaySession)
                        <a class="btn btn-primary" href="{{ route('teacher.modules.attendance.edit', [$module, $todaySession]) }}"><i class="ti ti-clipboard-check me-1"></i>Registrar asistencia</a>
                    @elseif($isCurrent)
                        <form method="POST" action="{{ route('teacher.modules.sessions.start', $module) }}">@csrf<button class="btn btn-success" type="submit"><i class="ti ti-player-play me-1"></i>Iniciar clase</button></form>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><x-ui.card><div class="card-body text-center text-secondary py-5"><i class="ti ti-books-off fs-1 d-block mb-2"></i>No tiene módulos asignados actualmente.</div></x-ui.card></div>
    @endforelse
</div>
@endsection
