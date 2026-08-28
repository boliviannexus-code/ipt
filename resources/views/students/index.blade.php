@extends('layouts.admin')
@section('title', 'Estudiantes')
@section('page-title', 'Estudiantes')
@section('page-subtitle', 'Estudiantes activos con inscripción vigente')
@section('content')
<div data-refresh-container>
    <x-ui.table-card title="Estudiantes habilitados">
        <x-slot:actions>
            <form class="d-flex gap-2" method="GET" action="{{ route('students.index') }}">
                <input class="form-control form-control-sm" name="q" value="{{ $search }}" placeholder="Buscar nombre, CI o cuenta" aria-label="Buscar estudiantes">
                <button class="btn btn-outline-primary btn-sm" type="submit"><i class="ti ti-search"></i></button>
            </form>
        </x-slot:actions>
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Cuenta / Sede</th><th>Estudiante</th><th>CI</th><th>Programa</th><th>Contacto</th><th>Módulos</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
            @forelse($students as $student)
                <tr>
                    <td><span class="fw-semibold d-block">{{ $student->account_number }}</span><small class="text-secondary">{{ $student->campus?->name ?? 'Sin sede' }}</small></td>
                    <td class="fw-semibold">{{ trim("{$student->first_name} {$student->paternal_surname} {$student->maternal_surname}") }}</td>
                    <td>{{ $student->identity_document }}</td>
                    <td>{{ $student->contracts->pluck('program.title')->unique()->join(', ') }}</td>
                    <td>{{ $student->email ?: '—' }}<small class="d-block text-secondary">{{ $student->phone ?: '—' }}</small></td>
                    <td><span class="badge text-bg-azure">{{ $student->module_assignments_count }}</span></td>
                    <td class="text-end"><div class="d-inline-flex gap-1"><a class="btn btn-outline-secondary btn-sm" href="{{ route('students.kardex.show', $student) }}"><i class="ti ti-file-description me-1" aria-hidden="true"></i>Kardex</a>@can('students.manage')<a class="btn btn-outline-primary btn-sm" href="{{ route('students.modules.create', $student) }}" data-modal-url="{{ route('students.modules.create', $student) }}" data-modal-title="Asignar estudiante a módulo" data-modal-size="lg"><i class="ti ti-book-upload me-1"></i>Asignar módulo</a>@endcan</div></td>
                </tr>
            @empty
                <x-ui.empty-row colspan="7" message="No existen estudiantes habilitados." />
            @endforelse
            </tbody>
        </table>
        <x-slot:footer>{{ $students->links() }}</x-slot:footer>
    </x-ui.table-card>
</div>
@endsection
