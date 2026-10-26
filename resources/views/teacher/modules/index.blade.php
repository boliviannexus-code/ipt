@extends('layouts.admin')
@section('title', 'Mis módulos')
@section('page-title', 'Mis módulos')
@section('page-subtitle', 'Módulos activos y registros de clase')
@section('content')
@if($errors->any())
    <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
@if(isset($teacherUnavailableMessage))
    <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
        <i class="ti ti-user-exclamation fs-2" aria-hidden="true"></i>
        <div><strong>No tienes un perfil docente vinculado.</strong><div>{{ $teacherUnavailableMessage }}</div></div>
    </div>
@endif
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div class="d-flex align-items-center gap-2"><h3 class="mb-0">Módulos activos</h3><span class="badge bg-primary-lt">{{ $modules->count() }}</span></div>
    <a class="btn btn-outline-secondary" href="{{ route('teacher.modules.history') }}"><i class="ti ti-history me-1" aria-hidden="true"></i>Historial mis módulos</a>
</div>
<div class="card teacher-course-list">
    @forelse($modules as $module)
        @php
            $todaySession = $module->classSessions->first(fn ($session) => $session->class_date->isToday());
            $pendingSessions = $module->classSessions->whereNull('ended_at');
            $defaultClassDate = today()->lt($module->start_date) ? $module->start_date : (today()->gt($module->end_date) ? $module->end_date : today());
        @endphp
        <article class="teacher-course" aria-labelledby="course-title-{{ $module->id }}">
            <div class="teacher-course-overview">
                <div class="teacher-course-title">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <h3 id="course-title-{{ $module->id }}" class="mb-0">{{ $module->name }}</h3>
                        <span class="badge {{ $module->modality === 'virtual' ? 'bg-azure-lt' : 'bg-green-lt' }}">{{ $module->modality === 'virtual' ? 'Virtual' : 'Presencial' }}</span>
                    </div>
                    <div class="text-secondary">{{ $module->program->title }} · {{ $module->level->name }}</div>
                </div>
                <div class="teacher-course-meta"><span class="text-secondary small">Horario</span><strong class="teacher-course-time">{{ substr($module->starts_at, 0, 5) }}–{{ substr($module->ends_at, 0, 5) }}</strong></div>
                <div class="teacher-course-meta"><span class="text-secondary small">Vigencia</span><span>{{ $module->start_date->format('d/m/Y') }}–{{ $module->end_date->format('d/m/Y') }}</span></div>
                <div class="teacher-course-meta"><span class="text-secondary small">Estudiantes</span><strong>{{ $module->student_assignments_count }}</strong></div>
            </div>
            @if($todaySession || today()->lt($module->start_date) || today()->gt($module->end_date) || ($pendingSessions->isNotEmpty() && auth()->user()->can('teaching.manage')))
            <div class="teacher-course-status d-flex flex-wrap align-items-center gap-2">
                @if($todaySession)
                    <span class="badge {{ $todaySession->ended_at ? 'bg-secondary-lt' : 'bg-success-lt' }}"><i class="ti {{ $todaySession->ended_at ? 'ti-lock-check' : 'ti-circle-check' }} me-1" aria-hidden="true"></i>{{ $todaySession->ended_at ? 'Clase finalizada a las '.$todaySession->ended_at->format('H:i') : 'Clase iniciada hoy a las '.$todaySession->started_at->format('H:i') }}</span>
                @elseif(today()->lt($module->start_date))
                    <span class="badge bg-secondary-lt">El módulo aún no inició.</span>
                @elseif(today()->gt($module->end_date))
                    <span class="badge bg-warning-lt">Pendiente de cierre</span>
                @endif
                @can('teaching.manage')
                    @foreach($pendingSessions as $pendingSession)
                        <a class="btn btn-primary btn-sm" href="{{ route('teacher.modules.attendance.edit', [$module, $pendingSession]) }}"><i class="ti ti-clipboard-check me-1" aria-hidden="true"></i>Continuar registro · {{ $pendingSession->class_date->format('d/m/Y') }}</a>
                    @endforeach
                @endcan
            </div>
            @endif
            <div class="teacher-course-actions">
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('teacher.tracking.show', $module) }}"><i class="ti ti-table me-1" aria-hidden="true"></i>Centralizador</a>
                    @can('teaching.manage')
                        <a class="btn btn-outline-secondary btn-sm" href="{{ route('teacher.modules.single-grades.edit', $module) }}"><i class="ti ti-list-check me-1" aria-hidden="true"></i>Ponderaciones únicas</a>
                        @if(today()->gte($module->end_date))
                            <a class="btn btn-outline-primary btn-sm" href="{{ route('teacher.modules.results.edit', $module) }}"><i class="ti ti-rosette-discount-check me-1" aria-hidden="true"></i>Finalizar curso</a>
                        @else
                            <button class="btn btn-outline-secondary btn-sm" type="button" disabled title="Disponible desde el {{ $module->end_date->format('d/m/Y') }}"><i class="ti ti-calendar-time me-1" aria-hidden="true"></i>Finalizar curso</button>
                        @endif
                    @endcan
                </div>
                @can('teaching.manage')
                    <form class="teacher-course-start" method="POST" action="{{ route('teacher.modules.sessions.start', $module) }}">
                        @csrf
                        <input type="hidden" name="module_id" value="{{ $module->id }}">
                        <label class="form-label mb-0 small" for="class-date-{{ $module->id }}">Fecha de la clase</label>
                        <input class="form-control form-control-sm" type="date" id="class-date-{{ $module->id }}" name="class_date" value="{{ (int) old('module_id') === $module->id ? old('class_date', $defaultClassDate->toDateString()) : $defaultClassDate->toDateString() }}" min="{{ $module->start_date->toDateString() }}" max="{{ $module->end_date->toDateString() }}" title="Solo una clase por fecha en este curso." required>
                        <button class="btn btn-success btn-sm" type="submit"><i class="ti ti-player-play me-1" aria-hidden="true"></i>Iniciar clase</button>
                    </form>
                @endcan
            </div>
        </article>
    @empty
        <div class="card-body text-center text-secondary py-5"><i class="ti ti-books-off fs-1 d-block mb-2" aria-hidden="true"></i>No tiene módulos activos asignados.<a class="d-block mt-2" href="{{ route('teacher.modules.history') }}">Consultar módulos finalizados</a></div>
    @endforelse
</div>
@endsection
