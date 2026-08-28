@extends('layouts.admin')
@section('title', 'Áreas | '.config('app.name'))
@section('page-title', 'Áreas')
@section('page-subtitle', 'Estructura organizacional por empresa')
@section('content')
<x-ui.table-card title="Listado de áreas">
 <x-slot:actions><a class="btn btn-primary btn-sm" href="{{ route('areas.create') }}">Nueva área</a></x-slot:actions>
 <table class="table table-hover align-middle"><thead><tr><th>Área</th><th>Cargos</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead><tbody>
 @forelse($areas as $area)<tr><td>{{ $area->name }}</td><td>{{ $area->positions_count }}</td><td><span class="badge text-bg-{{ $area->is_active ? 'success' : 'secondary' }}">{{ $area->is_active ? 'Activa' : 'Inactiva' }}</span></td><td class="text-end"><a class="btn btn-outline-primary btn-sm" href="{{ route('areas.edit',$area) }}">Editar</a> <form class="d-inline" method="POST" action="{{ route('areas.destroy',$area) }}" data-confirm-delete="¿Eliminar área?">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Eliminar</button></form></td></tr>
 @empty <x-ui.empty-row colspan="4" message="No hay áreas registradas."/> @endforelse
 </tbody></table><x-slot:footer>{{ $areas->links() }}</x-slot:footer>
</x-ui.table-card>
@endsection
