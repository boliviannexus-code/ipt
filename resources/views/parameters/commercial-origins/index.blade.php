@extends('layouts.admin')

@section('title', 'Origen comercial | '.config('app.name'))
@section('page-title', 'Origen comercial')
@section('page-subtitle', 'Orígenes comerciales de la empresa activa')

@section('content')
    <x-ui.table-card title="Listado de orígenes comerciales" data-refresh-container>
        <x-slot:actions>
            @can('commercial-origins.create')
                <a class="btn btn-primary btn-sm" href="{{ route('parameters.commercial-origins.create') }}" data-modal-url="{{ route('parameters.commercial-origins.create') }}" data-modal-title="Nuevo origen comercial">
                    <i class="ti ti-plus me-1" aria-hidden="true"></i>Nuevo origen comercial
                </a>
            @endcan
        </x-slot:actions>

        <table class="table table-hover align-middle">
            <thead><tr><th>Nombre</th><th>Creado</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
                @forelse ($commercialOrigins as $commercialOrigin)
                    <tr>
                        <td class="fw-semibold">{{ $commercialOrigin->name }}</td>
                        <td>{{ $commercialOrigin->created_at->format('d/m/Y') }}</td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                @can('commercial-origins.edit')
                                    <a class="btn btn-outline-primary btn-sm" href="{{ route('parameters.commercial-origins.edit', $commercialOrigin) }}" data-modal-url="{{ route('parameters.commercial-origins.edit', $commercialOrigin) }}" data-modal-title="Editar origen comercial">Editar</a>
                                @endcan
                                @can('commercial-origins.delete')
                                    <form method="POST" action="{{ route('parameters.commercial-origins.destroy', $commercialOrigin) }}" data-ajax-form data-refresh-url="{{ route('parameters.commercial-origins.index') }}" data-confirm-delete="¿Eliminar el origen comercial {{ $commercialOrigin->name }}?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm" type="submit" aria-label="Eliminar {{ $commercialOrigin->name }}"><i class="ti ti-trash" aria-hidden="true"></i></button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="3" message="No hay orígenes comerciales registrados." />
                @endforelse
            </tbody>
        </table>

        <x-slot:footer>{{ $commercialOrigins->links() }}</x-slot:footer>
    </x-ui.table-card>
@endsection
