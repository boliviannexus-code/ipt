@extends('layouts.admin')
@section('title', 'Mis módulos')
@section('page-title', 'Mis módulos')
@section('page-subtitle', 'Clases y asistencia de los módulos asignados')
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
<div class="row g-3">
    @forelse($modules as $module)
        @php($todaySession = $module->classSessions->first(fn ($session) => $session->class_date->isToday()))
        @php($defaultClassDate = today()->lt($module->start_date) ? $module->start_date : (today()->gt($module->end_date) ? $module->end_date : today()))
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
                    @if($todaySession)<div class="alert {{ $todaySession->ended_at ? 'alert-secondary' : 'alert-success' }} py-2 mb-0"><i class="ti {{ $todaySession->ended_at ? 'ti-lock-check' : 'ti-circle-check' }} me-1"></i>{{ $todaySession->ended_at ? 'Clase finalizada a las '.$todaySession->ended_at->format('H:i') : 'Clase iniciada hoy a las '.$todaySession->started_at->format('H:i') }}</div>@elseif(!$isCurrent)<div class="alert alert-secondary py-2 mb-0">{{ today()->lt($module->start_date) ? 'El módulo aún no inició.' : 'El módulo finalizó.' }}</div>@endif
                </div>
                <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
                    <a class="btn btn-outline-primary" href="{{ route('teacher.modules.single-grades.edit', $module) }}"><i class="ti ti-list-check me-1"></i>Ponderaciones únicas</a>
                    @if($module->closed_at || $module->studentResults->isNotEmpty())
                        <a class="btn btn-outline-primary" href="{{ route('teacher.modules.results.edit', $module) }}"><i class="ti ti-rosette-discount-check me-1"></i>Ver resultados</a>
                    @elseif(today()->gte($module->end_date))
                        <a class="btn btn-outline-primary" href="{{ route('teacher.modules.results.edit', $module) }}"><i class="ti ti-rosette-discount-check me-1"></i>Finalizar curso</a>
                    @else
                        <button class="btn btn-outline-secondary" type="button" disabled title="Disponible desde el {{ $module->end_date->format('d/m/Y') }}"><i class="ti ti-calendar-time me-1"></i>Finalizar curso</button>
                    @endif
                    @unless($module->closed_at || $module->studentResults->isNotEmpty())
                        @foreach($module->classSessions->whereNull('ended_at') as $pendingSession)
                            <a class="btn btn-primary" href="{{ route('teacher.modules.attendance.edit', [$module, $pendingSession]) }}"><i class="ti ti-clipboard-check me-1" aria-hidden="true"></i>Continuar registro · {{ $pendingSession->class_date->format('d/m/Y') }}</a>
                        @endforeach
                        @can('teaching.manage')
                            <form class="d-flex flex-wrap align-items-end gap-2 w-100" method="POST" action="{{ route('teacher.modules.sessions.start', $module) }}">
                                @csrf
                                <input type="hidden" name="module_id" value="{{ $module->id }}">
                                <div class="flex-grow-1">
                                    <label class="form-label" for="class-date-{{ $module->id }}">Fecha de la clase</label>
                                    <input class="form-control" type="date" id="class-date-{{ $module->id }}" name="class_date" value="{{ (int) old('module_id') === $module->id ? old('class_date', $defaultClassDate->toDateString()) : $defaultClassDate->toDateString() }}" min="{{ $module->start_date->toDateString() }}" max="{{ $module->end_date->toDateString() }}" aria-describedby="class-date-hint-{{ $module->id }}" required>
                                    <small class="text-secondary" id="class-date-hint-{{ $module->id }}">Solo una clase por fecha en este curso.</small>
                                </div>
                                <button class="btn btn-success" type="submit"><i class="ti ti-player-play me-1" aria-hidden="true"></i>Iniciar clase</button>
                            </form>
                        @endcan
                    @endunless
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><x-ui.card><div class="card-body text-center text-secondary py-5"><i class="ti ti-books-off fs-1 d-block mb-2"></i>No tiene módulos asignados actualmente.</div></x-ui.card></div>
    @endforelse
</div>
@endsection
