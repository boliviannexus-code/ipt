@extends('layouts.admin')

@section('title', 'Promover módulo | '.config('app.name'))
@section('page-title', 'Promover módulo')
@section('page-subtitle', 'Módulos cerrados pendientes de gestión académica')

@section('content')
    <div class="alert alert-info d-flex align-items-start gap-2" role="status">
        <i class="ti ti-info-circle fs-3" aria-hidden="true"></i>
        <div><strong>Promoción de módulos</strong><div class="text-secondary">Aquí se muestran todos los módulos cuyo cierre académico fue completado. Las acciones de promoción se definirán en la siguiente etapa.</div></div>
    </div>

    <x-ui.table-card title="Módulos cerrados">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Programa / Nivel</th><th>Módulo</th><th>Docente</th><th class="text-center">Estudiantes</th><th>Finalización</th><th>Cerrado por</th><th class="text-end">Acción</th></tr></thead>
            <tbody>
                @forelse($modules as $module)
                    <tr>
                        <td><span class="d-block fw-semibold">{{ $module->program->title }}</span><small class="text-secondary">{{ $module->level->name }}</small></td>
                        <td><span class="d-block fw-semibold">{{ $module->name }}</span><small class="text-secondary">{{ $module->end_date->format('d/m/Y') }}</small></td>
                        <td>{{ $module->currentTeacherAssignment?->personnel?->full_name ?? 'Sin docente' }}</td>
                        <td class="text-center"><span class="badge text-bg-secondary">{{ $module->student_results_count }} / {{ $module->student_assignments_count }}</span></td>
                        <td><span class="d-block">{{ $module->closed_at->format('d/m/Y') }}</span><small class="text-secondary">{{ $module->closed_at->format('H:i') }}</small></td>
                        <td>{{ $module->closedBy?->name ?? 'Sistema' }}</td>
                        <td class="text-end"><button class="btn btn-outline-primary btn-sm" type="button" disabled title="Las acciones de promoción se definirán próximamente"><i class="ti ti-arrow-big-up-lines me-1" aria-hidden="true"></i>Promover</button></td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="7" message="Todavía no existen módulos cerrados para promover." />
                @endforelse
            </tbody>
        </table>
        <x-slot:footer>{{ $modules->links() }}</x-slot:footer>
    </x-ui.table-card>
@endsection
