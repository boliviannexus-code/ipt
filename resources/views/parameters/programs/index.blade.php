@extends('layouts.admin')
@section('title', 'Programas')
@section('page-title', 'Programas')
@section('page-subtitle', 'Programas académicos y planes vinculados')
@section('content')
<x-ui.table-card title="Listado de programas">
    <x-slot:actions>@can('programs.create')<a class="btn btn-primary btn-sm" href="{{ route('parameters.programs.create') }}"><i class="ti ti-plus me-1"></i>Nuevo programa</a>@endcan</x-slot:actions>
    <table class="table table-hover align-middle">
        <thead><tr><th>Título</th><th>Código matrícula</th><th>Duración</th><th>Planes</th><th>Niveles</th><th class="text-end">Acciones</th></tr></thead>
        <tbody>@forelse($programs as $program)<tr><td class="fw-semibold">{{ $program->title }}</td><td><span class="badge text-bg-secondary">{{ $program->enrollment_code ?: 'Pendiente' }}</span></td><td>{{ $program->duration_months }} meses</td><td><span class="badge text-bg-azure">{{ $program->plans_count }}</span></td><td>{{ $program->levels_count }}</td><td class="text-end"><div class="d-inline-flex gap-1">@can('programs.edit')<a class="btn btn-outline-secondary btn-sm" href="{{ route('parameters.programs.levels.index', $program) }}"><i class="ti ti-list-numbers me-1"></i>Configurar niveles</a><a class="btn btn-outline-primary btn-sm" href="{{ route('parameters.programs.edit', $program) }}">Editar</a>@endcan</div></td></tr>@empty<x-ui.empty-row colspan="6" message="No hay programas registrados." />@endforelse</tbody>
    </table>
    <x-slot:footer>{{ $programs->links() }}</x-slot:footer>
</x-ui.table-card>
@endsection
