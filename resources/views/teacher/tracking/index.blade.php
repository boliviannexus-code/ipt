@extends('layouts.admin')
@section('title', 'Seguimiento académico')
@section('page-title', 'Seguimiento')
@section('page-subtitle', 'Centralizadores de los módulos asignados')
@section('content')
@if($teacherUnavailableMessage)
    <div class="alert alert-warning d-flex align-items-start gap-2" role="alert"><i class="ti ti-user-exclamation fs-2" aria-hidden="true"></i><div><strong>No tienes un perfil docente vinculado.</strong><div>{{ $teacherUnavailableMessage }}</div></div></div>
@endif
<div class="row g-3">
    @forelse($modules as $module)
        @php($moduleStatus = today()->lt($module->start_date) ? 'Programado' : (today()->gt($module->end_date) ? 'Finalizado' : 'Activo'))
        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3"><div><h3 class="card-title mb-1">{{ $module->name }}</h3><div class="text-secondary">{{ $module->program->title }} · {{ $module->level->name }}</div></div><span class="badge {{ $moduleStatus === 'Activo' ? 'text-bg-green' : 'text-bg-secondary' }}">{{ $moduleStatus }}</span></div>
                    <div class="d-flex gap-4"><div><span class="text-secondary small d-block">Estudiantes</span><strong>{{ $module->student_assignments_count }}</strong></div><div><span class="text-secondary small d-block">Clases registradas</span><strong>{{ $module->class_sessions_count }}</strong></div></div>
                </div>
                <div class="card-footer"><a class="btn btn-primary w-100" href="{{ route('teacher.tracking.show', $module) }}"><i class="ti ti-table me-1"></i>Ver centralizador de notas</a></div>
            </div>
        </div>
    @empty
        <div class="col-12"><x-ui.card><div class="card-body text-center text-secondary py-5"><i class="ti ti-chart-dots-3 fs-1 d-block mb-2"></i>No tienes módulos asignados para seguimiento.</div></x-ui.card></div>
    @endforelse
</div>
@endsection
