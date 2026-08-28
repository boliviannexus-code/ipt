@extends('layouts.admin')
@section('title', 'Módulos académicos')
@section('page-title', 'Módulos')
@section('page-subtitle', 'Programa, nivel, modalidad y horarios')
@section('content')
<div data-refresh-container>
    <x-ui.table-card title="Listado de módulos">
        <x-slot:actions>@can('academic-modules.manage')<a class="btn btn-primary btn-sm" href="{{ route('academic.modules.create') }}" data-modal-url="{{ route('academic.modules.create') }}" data-modal-title="Nuevo módulo" data-modal-size="lg"><i class="ti ti-plus me-1"></i>Nuevo módulo</a>@endcan</x-slot:actions>
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Módulo</th><th>Programa</th><th>Nivel</th><th>Docente</th><th>Modalidad</th><th>Horario</th><th>Fechas</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>@forelse($modules as $module)<tr>
                <td class="fw-semibold">{{ $module->name }}</td><td>{{ $module->program->title }}</td><td>{{ $module->level->name }}</td>
                <td>{{ $module->currentTeacherAssignment?->personnel?->full_name ?? 'Sin asignar' }}</td>
                <td><span class="badge {{ $module->modality === 'virtual' ? 'text-bg-azure' : 'text-bg-green' }}">{{ $module->modality === 'virtual' ? 'Virtual' : 'Presencial' }}</span></td>
                <td>{{ substr($module->starts_at, 0, 5) }}–{{ substr($module->ends_at, 0, 5) }}</td>
                <td>{{ $module->start_date->format('d/m/Y') }}–{{ $module->end_date->format('d/m/Y') }}</td>
                <td class="text-end">@can('academic-modules.manage')<div class="d-inline-flex gap-1"><a class="btn btn-outline-success btn-sm" href="{{ route('academic.modules.teacher.edit', $module) }}" data-modal-url="{{ route('academic.modules.teacher.edit', $module) }}" data-modal-title="Asignar docente" data-modal-size="lg"><i class="ti ti-user-check me-1"></i>Docente</a><a class="btn btn-outline-primary btn-sm" href="{{ route('academic.modules.edit', $module) }}" data-modal-url="{{ route('academic.modules.edit', $module) }}" data-modal-title="Editar módulo" data-modal-size="lg">Editar</a><form method="POST" action="{{ route('academic.modules.destroy', $module) }}" data-ajax-form data-refresh-url="{{ route('academic.modules.index') }}" data-confirm-delete="¿Eliminar el módulo {{ $module->name }}?">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm" type="submit" aria-label="Eliminar {{ $module->name }}"><i class="ti ti-trash"></i></button></form></div>@endcan</td>
            </tr>@empty<x-ui.empty-row colspan="8" message="No existen módulos académicos registrados." />@endforelse</tbody>
        </table>
        <x-slot:footer>{{ $modules->links() }}</x-slot:footer>
    </x-ui.table-card>
</div>
@endsection
