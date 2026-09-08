@extends('layouts.admin')

@section('title', 'Promover módulo | '.config('app.name'))
@section('page-title', 'Promover módulo')
@section('page-subtitle', 'Módulos cerrados pendientes de gestión académica')

@section('content')
    <div class="alert alert-info d-flex align-items-start gap-2" role="status">
        <i class="ti ti-info-circle fs-3" aria-hidden="true"></i>
        <div><strong>Promoción de módulos</strong><div class="text-secondary">Crea el módulo del siguiente nivel, selecciona estudiantes disponibles y genera su nuevo cargo.</div></div>
    </div>

    <x-ui.table-card title="Módulos cerrados">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Programa / Nivel</th><th>Módulo</th><th>Docente</th><th class="text-center">Estudiantes</th><th>Finalización</th><th>Cerrado por</th><th class="text-end">Acción</th></tr></thead>
            <tbody>
                @forelse($modules as $module)
                    @php($nextLevel = $module->program->levels->where('is_active', true)->where('position', '>', $module->level->position)->sortBy('position')->first())
                    <tr>
                        <td><span class="d-block fw-semibold">{{ $module->program->title }}</span><small class="text-secondary">{{ $module->level->name }}</small></td>
                        <td><span class="d-block fw-semibold">{{ $module->name }}</span><small class="text-secondary">{{ $module->end_date->format('d/m/Y') }}</small></td>
                        <td>{{ $module->currentTeacherAssignment?->personnel?->full_name ?? 'Sin docente' }}</td>
                        <td class="text-center"><span class="badge text-bg-secondary">{{ $module->student_results_count }} / {{ $module->student_assignments_count }}</span></td>
                        <td><span class="d-block">{{ $module->closed_at->format('d/m/Y') }}</span><small class="text-secondary">{{ $module->closed_at->format('H:i') }}</small></td>
                        <td>{{ $module->closedBy?->name ?? 'Sistema' }}</td>
                        <td class="text-end">@if($nextLevel)<a class="btn btn-outline-primary btn-sm" href="{{ route('academic.promotions.create', $module) }}"><i class="ti ti-arrow-big-up-lines me-1" aria-hidden="true"></i>Promover</a>@else<span class="badge text-bg-secondary">Último nivel</span>@endif</td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="7" message="Todavía no existen módulos cerrados para promover." />
                @endforelse
            </tbody>
        </table>
        <x-slot:footer>{{ $modules->links() }}</x-slot:footer>
    </x-ui.table-card>
@endsection
