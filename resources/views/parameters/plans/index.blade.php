@extends('layouts.admin')

@section('title', 'Planes | '.config('app.name'))
@section('page-title', 'Planes')
@section('page-subtitle', 'Selecciona un programa para administrar sus planes')

@section('content')
    <x-ui.table-card title="Programas" data-refresh-container>
        <table class="table table-hover align-middle">
            <thead><tr><th>Programa</th><th>Código</th><th class="text-center">Planes</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
                @forelse ($programs as $program)
                    <tr>
                        <td class="fw-semibold">{{ $program->title }}</td>
                        <td><span class="badge text-bg-secondary">{{ $program->enrollment_code ?: 'Pendiente' }}</span></td>
                        <td class="text-center"><span class="badge text-bg-azure">{{ $program->plans_count }}</span></td>
                        <td class="text-end"><a class="btn btn-outline-primary btn-sm" href="{{ route('parameters.plans.show', $program) }}"><i class="ti ti-list me-1"></i>Ver planes</a></td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="4" message="No hay programas registrados. Primero crea un programa." />
                @endforelse
            </tbody>
        </table>

        <x-slot:footer>{{ $programs->links() }}</x-slot:footer>
    </x-ui.table-card>
@endsection
