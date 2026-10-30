@extends('layouts.admin')
@section('title', 'Datos de titular y estudiante')
@section('page-title', 'Datos de titular y estudiante')
@section('page-subtitle', 'Consulta y corrección de datos personales')
@section('content')
<form method="GET" class="mb-3" action="{{ route('academic.personal-data.index') }}">
    <label class="form-label" for="personal-data-search">Buscar por nombre, CI o matrícula del titular o estudiante</label>
    <div class="d-flex gap-2"><input class="form-control" id="personal-data-search" name="q" value="{{ $search }}" type="search"><button class="btn btn-primary" type="submit">Buscar</button></div>
</form>
<x-ui.table-card title="Titulares y estudiantes">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Matrícula</th><th>Titular</th><th>Estudiante</th><th>Programa</th><th>Acción</th></tr></thead>
            <tbody>
            @forelse($applications as $application)
                <tr>
                    <td>{{ $application->account_number ?? $application->student->account_number ?? '—' }}</td>
                    <td>{{ trim("{$application->first_name} {$application->paternal_surname} {$application->maternal_surname}") }}<small class="d-block text-secondary">{{ $application->identity_document }}</small></td>
                    <td>{{ trim("{$application->student->first_name} {$application->student->paternal_surname} {$application->student->maternal_surname}") }}<small class="d-block text-secondary">{{ $application->student->identity_document }}</small></td>
                    <td>{{ $application->program?->title ?? '—' }}</td>
                    <td><a class="btn btn-outline-primary" href="{{ route('academic.personal-data.edit', $application) }}">Editar datos</a></td>
                </tr>
            @empty
                <x-ui.empty-row colspan="5" message="No se encontraron registros." />
            @endforelse
            </tbody>
        </table>
    </div>
    <x-slot:footer>{{ $applications->links() }}</x-slot:footer>
</x-ui.table-card>
@endsection
