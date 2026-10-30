@extends('layouts.admin')
@section('title', 'Servicios adicionales')
@section('page-title', 'Servicios adicionales')
@section('page-subtitle', 'Configuración de servicios y costos de la empresa activa')
@section('content')
<x-ui.table-card title="Servicios configurados">
    <x-slot:actions>@can('additional-services.manage')<a class="btn btn-primary btn-sm" href="{{ route('additional-services.create') }}">Nuevo servicio</a>@endcan</x-slot:actions>
    <table class="table table-hover align-middle"><thead><tr><th>Título</th><th>Detalle</th><th class="text-end">Costo</th><th>Estado</th><th class="text-end">Acción</th></tr></thead><tbody>
        @forelse($services as $service)
            <tr><td>{{ $service->title }}</td><td class="text-break">{{ $service->detail }}</td><td class="text-end text-nowrap">Bs {{ number_format($service->cost, 2, ',', '.') }}</td><td><span class="badge {{ $service->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $service->is_active ? 'Activo' : 'Inactivo' }}</span></td><td class="text-end">@can('additional-services.manage')<a class="btn btn-outline-primary btn-sm" href="{{ route('additional-services.edit', $service) }}">Editar</a>@endcan</td></tr>
        @empty
            <x-ui.empty-row colspan="5" message="No hay servicios adicionales configurados." />
        @endforelse
    </tbody></table>
    <x-slot:footer>{{ $services->links() }}</x-slot:footer>
</x-ui.table-card>
@endsection
