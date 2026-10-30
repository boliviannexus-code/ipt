@extends('layouts.admin')
@section('title', 'Cargos por estudiante')
@section('page-title', 'Cargos por estudiante')
@section('page-subtitle', 'Consulta de cuentas y registro de cargos adicionales')
@section('content')
<x-ui.table-card title="Estudiantes de la empresa activa">
    <x-slot:actions><form class="d-flex gap-2" data-live-search="#student-charge-results" method="GET" action="{{ route('additional-charges.index') }}"><input class="form-control form-control-sm" type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Nombre, CI o matrícula" aria-label="Buscar estudiantes"><button class="btn btn-outline-primary btn-sm" type="submit">Buscar</button><span class="small text-secondary align-self-center" data-live-search-status role="status" aria-live="polite"></span></form></x-slot:actions>
    <div id="student-charge-results" aria-label="Resultados de estudiantes">
    <table class="table table-hover align-middle"><thead><tr><th>Estudiante / CI</th><th>Matrícula</th><th>Sede</th><th>Cuentas</th><th class="text-end">Acción</th></tr></thead><tbody>
        @forelse($students as $student)
            <tr><td>{{ trim($student->first_name.' '.$student->paternal_surname.' '.$student->maternal_surname) }}<small class="d-block text-secondary">{{ $student->identity_document }}{{ $student->is_active ? '' : ' · Inactivo' }}</small></td><td>{{ $student->account_number ?: '—' }}</td><td>{{ $student->campus?->short ?: '—' }}</td><td>{{ $student->contracts_count }}</td><td class="text-end"><a class="btn btn-outline-primary btn-sm" href="{{ route('additional-charges.show', $student) }}">Ver cargos</a></td></tr>
        @empty
            <x-ui.empty-row colspan="5" message="No se encontraron estudiantes." />
        @endforelse
    </tbody></table>
    <div class="p-3 border-top">{{ $students->links() }}</div>
    </div>
</x-ui.table-card>
@endsection
