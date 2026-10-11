@extends('layouts.admin')
@section('title', 'Control académico | '.config('app.name'))
@section('page-title', 'Control académico')
@section('page-subtitle', 'Selecciona un programa para asignar las ponderaciones de sus niveles')
@section('content')
<x-ui.table-card title="Programas disponibles">
    <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Programa</th><th>Código</th><th class="text-center">Niveles</th><th class="text-center">Módulos</th><th class="text-end">Acción</th></tr></thead>
        <tbody>
            @forelse($programs as $program)
                <tr>
                    <td class="fw-semibold">{{ $program->title }}</td>
                    <td>{{ $program->enrollment_code ?: 'Pendiente' }}</td>
                    <td class="text-center">{{ $program->levels_count }}</td>
                    <td class="text-center">{{ $program->academic_modules_count }}</td>
                    <td class="text-end"><a class="btn btn-outline-primary btn-sm" href="{{ route('academic.control.levels.edit', $program) }}" aria-label="Asignar ponderaciones a {{ $program->title }}"><i class="ti ti-scale me-1" aria-hidden="true"></i>Ponderaciones</a></td>
                </tr>
            @empty
                <x-ui.empty-row colspan="5" message="No hay programas registrados para la empresa activa." />
            @endforelse
        </tbody>
    </table>
    <x-slot:footer>{{ $programs->links() }}</x-slot:footer>
</x-ui.table-card>
@endsection
