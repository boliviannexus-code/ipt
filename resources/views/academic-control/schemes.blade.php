@extends('layouts.admin')
@section('title', 'Ponderaciones | '.config('app.name'))
@section('page-title', 'Ponderaciones')
@section('page-subtitle', 'Configura las ponderaciones que puedes reutilizar en diferentes programas y niveles')
@section('content')
@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
<div class="alert alert-info">Puedes crear varias ponderaciones y mantenerlas activas. Para vincularlas a un nivel, abre el programa desde Control académico.</div>
<x-ui.table-card title="Catálogo de ponderaciones">
    @can('academic-control.manage')
        <x-slot:actions><button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#createSchemeModal"><i class="ti ti-plus me-1"></i>Crear ponderación</button></x-slot:actions>
    @endcan
    <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Nombre</th><th>Nota mínima</th><th>Estado</th><th class="text-end">Acción</th></tr></thead>
        <tbody>
        @forelse($schemes as $scheme)
            <tr>
                <td class="fw-semibold">{{ $scheme->name }}</td><td>{{ $scheme->passing_score }}</td>
                <td><span class="badge {{ $scheme->is_active ? 'bg-green-lt text-green' : 'bg-yellow-lt text-yellow' }}">{{ $scheme->is_active ? 'Activa' : 'Borrador' }}</span></td>
                <td class="text-end"><a class="btn btn-outline-primary btn-sm" href="{{ route('academic.weightings.show', $scheme) }}">{{ $scheme->isFinalized() ? 'Ver configuración' : 'Configurar' }}</a>
                    @can('academic-control.manage')
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#renameSchemeModal{{ $scheme->id }}" aria-label="Cambiar nombre de {{ $scheme->name }}"><i class="ti ti-pencil me-1" aria-hidden="true"></i>Cambiar nombre</button>
                        @if($scheme->canBeDeleted())
                            <form class="d-inline" method="POST" action="{{ route('academic.weightings.destroy', $scheme) }}" data-confirm-action data-confirm-title="¿Eliminar esta ponderación?" data-confirm-text="Se eliminarán su configuración y habilidades. Esta acción no se puede deshacer." data-confirm-button="Sí, eliminar ponderación">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm" type="submit" aria-label="Eliminar ponderación {{ $scheme->name }}"><i class="ti ti-trash me-1" aria-hidden="true"></i>Eliminar</button>
                            </form>
                        @else
                            <span class="text-secondary small ms-2">Vinculada</span>
                        @endif
                    @endcan
                </td>
            </tr>
        @empty
            <x-ui.empty-row colspan="4" message="No hay ponderaciones creadas." />
        @endforelse
        </tbody>
    </table>
</x-ui.table-card>
@can('academic-control.manage')
@foreach($schemes as $scheme)
    @include('academic-control.partials.rename-modal', ['scheme' => $scheme])
@endforeach
<div class="modal modal-blur fade" id="createSchemeModal" tabindex="-1" aria-labelledby="createSchemeTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('academic.weightings.store') }}">
            @csrf
            <div class="modal-header"><h2 class="modal-title" id="createSchemeTitle">Crear ponderación independiente</h2><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <div class="modal-body">
                <label class="form-label required" for="scheme-name">Nombre</label><input class="form-control mb-3" id="scheme-name" name="name" maxlength="150" value="{{ old('name') }}" required>
                <label class="form-label" for="source-scheme">Configuración inicial</label>
                <select class="form-select" id="source-scheme" name="source_scheme_id"><option value="">Borrador vacío</option>@foreach($schemes->where('status', 'finalized') as $source)<option value="{{ $source->id }}">Copiar {{ $source->name }}</option>@endforeach</select>
                <div class="form-hint">La copia puede editarse y activarse sin modificar la configuración original.</div>
            </div>
            <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Crear ponderación</button></div>
        </form>
    </div>
</div>
@endcan
@endsection
