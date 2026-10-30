@extends('layouts.admin')
@section('title', 'Examen de nivel')
@section('page-title', 'Examen de nivel')
@section('page-subtitle', 'Estudiantes inscritos sin módulo activo')
@section('content')
<x-ui.table-card title="Estudiantes disponibles">
    <x-slot:actions>
        <div class="d-flex flex-wrap gap-2">
            <form class="d-flex gap-2" method="GET" action="{{ route('placement-exams.index') }}">
                <input class="form-control form-control-sm" type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Nombre, CI o matrícula" aria-label="Buscar estudiantes">
                <button class="btn btn-outline-primary btn-sm" type="submit">Buscar</button>
            </form>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('placement-exams.history') }}">Historial de exámenes</a>
        </div>
    </x-slot:actions>
    <table class="table table-hover align-middle">
        <thead><tr><th>Estudiante / CI</th><th>Matrícula</th><th>Programas</th><th>Sede</th><th class="text-end">Acción</th></tr></thead>
        <tbody>
        @forelse($students as $student)
            <tr>
                <td>{{ trim($student->first_name.' '.$student->paternal_surname.' '.$student->maternal_surname) }}<small class="d-block text-secondary">{{ $student->identity_document }}</small></td>
                <td>{{ $student->account_number ?: '—' }}</td>
                <td>{{ $student->contracts->pluck('program.title')->unique()->join(', ') }}</td>
                <td>{{ $student->campus?->short ?: '—' }}</td>
                <td class="text-end">@can('students.manage')<a class="btn btn-primary btn-sm" href="{{ route('placement-exams.create', $student) }}">Registrar examen</a>@endcan</td>
            </tr>
        @empty
            <x-ui.empty-row colspan="5" message="No se encontraron estudiantes inscritos sin módulo activo." />
        @endforelse
        </tbody>
    </table>
    <x-slot:footer>{{ $students->links() }}</x-slot:footer>
</x-ui.table-card>
@endsection
