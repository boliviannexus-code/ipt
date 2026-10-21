@extends('layouts.admin')
@section('title', 'Estudiantes')
@section('page-title', 'Estudiantes')
@section('page-subtitle', 'Estudiantes inscritos y seguimiento de asistencia')
@section('content')
<div data-refresh-container>
    <x-ui.table-card title="Estudiantes y estados de matrícula">
        <x-slot:actions>
            <form class="d-flex gap-2" method="GET" action="{{ route('students.index') }}">
                <input class="form-control form-control-sm" name="q" value="{{ $search }}" placeholder="Buscar nombre, CI o matrícula" aria-label="Buscar estudiantes">
                <button class="btn btn-outline-primary btn-sm" type="submit"><i class="ti ti-search"></i></button>
            </form>
        </x-slot:actions>
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Matrícula / Sede</th><th>Estudiante</th><th>Nivel del estudiante</th><th class="table-email-column">Contacto</th><th>Módulo actual</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
            @forelse($students as $student)
                @php($isAttendanceInactive = $student->contracts->contains('status', 'inactive'))
                <tr @class(['student-attendance-inactive' => $isAttendanceInactive])>
                    @php($studentLevels = $student->contracts->map(fn($contract) => $contract->currentProgramLevel ?? $contract->program->levels->firstWhere('position', 1))->filter()->pluck('name')->unique()->join(', '))
                    <td><span class="fw-semibold d-block">{{ $student->account_number }}</span><small class="text-secondary">{{ $student->campus?->name ?? 'Sin sede' }}</small></td>
                    <td class="fw-semibold">{{ trim("{$student->first_name} {$student->paternal_surname} {$student->maternal_surname}") }}@if($isAttendanceInactive)<small class="d-block text-orange">INACTIVO · Asistencia irregular</small>@endif
                        @foreach($student->contracts->whereIn('status', ['suspended', 'abandoned', 'withdrawn', 'finished']) as $stateContract)
                            <small class="d-block {{ $stateContract->status === 'withdrawn' ? 'text-danger' : 'text-secondary' }}">{{ $stateContract->program->title }} · {{ $stateContract->statusLabel() }}@if($stateContract->reactivation_date) · Retorno: {{ $stateContract->reactivation_date->format('d/m/Y') }}@endif</small>
                        @endforeach</td>
                    <td>{{ $studentLevels !== '' ? $studentLevels : 'Sin nivel' }}</td>
                    <td class="table-email-column"><span class="table-email" title="{{ $student->email ?: '—' }}">{{ $student->email ?: '—' }}</span><small class="d-block text-secondary">{{ $student->phone ?: '—' }}</small></td>
                    <td>@if($student->currentModuleAssignment)<span class="fw-semibold">{{ $student->currentModuleAssignment->module->name }} / {{ $student->currentModuleAssignment->module->modality === 'virtual' ? 'Virtual' : 'Presencial' }}</span>@else<span class="badge text-bg-secondary">Sin módulo</span>@endif</td>
                    <td class="text-end">
                        <div class="d-flex flex-wrap justify-content-end gap-1">
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('students.kardex.show', $student) }}"><i class="ti ti-file-description me-1" aria-hidden="true"></i>Kardex</a>
                            @can('students.manage')
                                <a class="btn btn-outline-warning btn-sm" href="{{ route('students.status.edit', $student) }}" data-modal-url="{{ route('students.status.edit', $student) }}" data-modal-title="Cambiar estado de matrícula" data-modal-size="lg">Cambiar estado</a>
                                @if(!$student->currentModuleAssignment && $student->contracts->contains('status', 'enrolled'))
                                    <a class="btn btn-outline-primary btn-sm" href="{{ route('students.modules.create', $student) }}" data-modal-url="{{ route('students.modules.create', $student) }}" data-modal-title="Asignar estudiante a módulo" data-modal-size="lg"><i class="ti ti-book-upload me-1" aria-hidden="true"></i>Asignar módulo</a>
                                @endif
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <x-ui.empty-row colspan="6" message="No existen estudiantes habilitados." />
            @endforelse
            </tbody>
        </table>
        <x-slot:footer>{{ $students->links() }}</x-slot:footer>
    </x-ui.table-card>
</div>
@endsection
