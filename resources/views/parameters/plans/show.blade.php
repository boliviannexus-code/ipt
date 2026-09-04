@extends('layouts.admin')

@section('title', 'Planes de '.$program->title.' | '.config('app.name'))
@section('page-title', 'Planes de '.$program->title)
@section('page-subtitle', 'Planes y costos mensuales del programa seleccionado')

@section('content')
    <div class="mb-3"><a class="btn btn-outline-secondary" href="{{ route('parameters.plans.index') }}"><i class="ti ti-arrow-left me-1"></i>Volver a programas</a></div>

    <x-ui.table-card title="Listado de planes" data-refresh-container>
        <x-slot:actions>
            @can('plans.create')
                <a class="btn btn-primary btn-sm" href="{{ route('parameters.plans.create', $program) }}" data-modal-url="{{ route('parameters.plans.create', $program) }}" data-modal-title="Nuevo plan para {{ $program->title }}"><i class="ti ti-plus me-1"></i>Nuevo plan</a>
            @endcan
        </x-slot:actions>
        <table class="table table-hover align-middle">
            <thead><tr><th>Nombre</th><th class="text-end">Costo mensual</th><th>Creado</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
                @forelse ($plans as $plan)
                    <tr><td class="fw-semibold">{{ $plan->name }}</td><td class="text-end">Bs {{ number_format((float) $plan->monthly_cost, 2, ',', '.') }}</td><td>{{ $plan->created_at->format('d/m/Y') }}</td><td class="text-end">@can('plans.edit')<a class="btn btn-outline-primary btn-sm" href="{{ route('parameters.plans.edit', [$program, $plan]) }}" data-modal-url="{{ route('parameters.plans.edit', [$program, $plan]) }}" data-modal-title="Editar plan">Editar</a>@endcan</td></tr>
                @empty
                    <x-ui.empty-row colspan="4" message="Este programa todavía no tiene planes." />
                @endforelse
            </tbody>
        </table>
        <x-slot:footer>{{ $plans->links() }}</x-slot:footer>
    </x-ui.table-card>
@endsection
