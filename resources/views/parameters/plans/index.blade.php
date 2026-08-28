@extends('layouts.admin')

@section('title', 'Planes | '.config('app.name'))
@section('page-title', 'Planes')
@section('page-subtitle', 'Planes y costos mensuales de la empresa activa')

@section('content')
    <x-ui.table-card title="Listado de planes" data-refresh-container>
        <x-slot:actions>
            @can('plans.create')
                <a class="btn btn-primary btn-sm" href="{{ route('parameters.plans.create') }}" data-modal-url="{{ route('parameters.plans.create') }}" data-modal-title="Nuevo plan">
                    <i class="ti ti-plus me-1"></i>Nuevo plan
                </a>
            @endcan
        </x-slot:actions>

        <table class="table table-hover align-middle">
            <thead><tr><th>Nombre</th><th class="text-end">Costo mensual</th><th>Creado</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
                @forelse ($plans as $plan)
                    <tr>
                        <td class="fw-semibold">{{ $plan->name }}</td>
                        <td class="text-end">Bs {{ number_format((float) $plan->monthly_cost, 2, ',', '.') }}</td>
                        <td>{{ $plan->created_at->format('d/m/Y') }}</td>
                        <td class="text-end">@can('plans.edit')<a class="btn btn-outline-primary btn-sm" href="{{ route('parameters.plans.edit', $plan) }}" data-modal-url="{{ route('parameters.plans.edit', $plan) }}" data-modal-title="Editar plan">Editar</a>@endcan</td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="4" message="No hay planes registrados." />
                @endforelse
            </tbody>
        </table>

        <x-slot:footer>{{ $plans->links() }}</x-slot:footer>
    </x-ui.table-card>
@endsection
