@extends('layouts.admin')
@section('title', request()->routeIs('all-modules.*') ? 'Historial de todos los módulos' : 'Historial mis módulos')
@section('page-title', request()->routeIs('all-modules.*') ? 'Historial de todos los módulos' : 'Historial mis módulos')
@section('page-subtitle', 'Módulos finalizados, del más reciente al más antiguo')
@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <a class="btn btn-outline-secondary" href="{{ module_workspace_route('teacher.modules.index') }}"><i class="ti ti-arrow-left me-1" aria-hidden="true"></i>{{ request()->routeIs('all-modules.*') ? 'Volver a todos los módulos' : 'Volver a mis módulos' }}</a>
    <span class="text-secondary">{{ $modules->total() }} módulos finalizados</span>
</div>
@if($teacherUnavailableMessage)
    <div class="alert alert-warning" role="alert">{{ $teacherUnavailableMessage }}</div>
@endif
<x-ui.table-card title="Módulos finalizados">
    <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Módulo / Programa</th><th>Modalidad / Horario</th><th>Vigencia</th><th class="text-center">Estudiantes</th><th>Cierre</th><th class="text-end">Consulta</th></tr></thead>
        <tbody>
        @forelse($modules as $module)
            <tr>
                <td><strong class="d-block">{{ $module->name }}</strong>@if(request()->routeIs('all-modules.*'))<small class="d-block text-secondary">Docente: {{ $module->currentTeacherAssignment?->personnel?->full_name ?? 'Sin docente asignado' }}</small>@endif<small class="text-secondary">{{ $module->program->title }} · {{ $module->level->name }}</small></td>
                <td><span class="badge {{ $module->modality === 'virtual' ? 'bg-azure-lt' : 'bg-green-lt' }}">{{ $module->modality === 'virtual' ? 'Virtual' : 'Presencial' }}</span><small class="d-block text-secondary mt-1">{{ substr($module->starts_at, 0, 5) }}–{{ substr($module->ends_at, 0, 5) }}</small></td>
                <td><span class="d-block">{{ $module->start_date->format('d/m/Y') }}</span><small class="text-secondary">al {{ $module->end_date->format('d/m/Y') }}</small></td>
                <td class="text-center">{{ $module->student_assignments_count }}</td>
                <td>{{ $module->closed_at?->format('d/m/Y') ?? $module->end_date->format('d/m/Y') }}<small class="d-block text-secondary">{{ $module->class_sessions_count }} clases</small></td>
                <td><div class="d-flex flex-wrap justify-content-end gap-2"><a class="btn btn-outline-primary btn-sm" href="{{ module_workspace_route('teacher.tracking.show', $module) }}">Centralizador</a>@if(module_workspace_can_manage())<a class="btn btn-outline-secondary btn-sm" href="{{ module_workspace_route('teacher.modules.results.edit', $module) }}">Ver resultados</a>@endif</div></td>
            </tr>
        @empty
            <x-ui.empty-row colspan="6" message="No se encontraron módulos finalizados." />
        @endforelse
        </tbody>
    </table>
    <x-slot:footer>{{ $modules->links() }}</x-slot:footer>
</x-ui.table-card>
@endsection
