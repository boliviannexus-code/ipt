@extends('layouts.admin')

@section('title', 'Sedes | '.config('app.name'))
@section('page-title', 'Sedes')
@section('page-subtitle', 'Sedes de la empresa activa')

@section('content')
    <x-ui.table-card title="Listado de sedes" data-refresh-container>
        <x-slot:actions>
            @can('campuses.manage')
                <a class="btn btn-primary btn-sm" href="{{ route('campuses.create') }}" data-modal-url="{{ route('campuses.create') }}" data-modal-title="Nueva sede">
                    <i class="ti ti-plus me-1" aria-hidden="true"></i>Nueva sede
                </a>
            @endcan
        </x-slot:actions>

        <table class="table table-hover align-middle">
            <thead><tr><th>Nombre</th><th>Código</th><th>Dirección</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
                @forelse ($campuses as $campus)
                    <tr>
                        <td class="fw-semibold">{{ $campus->name }}</td>
                        <td><span class="badge text-bg-secondary">{{ $campus->code }}</span></td>
                        <td>{{ $campus->address }}</td>
                        <td class="text-end">
                            @can('campuses.manage')
                                <div class="d-inline-flex gap-1">
                                    <a class="btn btn-outline-primary btn-sm" href="{{ route('campuses.edit', $campus) }}" data-modal-url="{{ route('campuses.edit', $campus) }}" data-modal-title="Editar sede">Editar</a>
                                    <form method="POST" action="{{ route('campuses.destroy', $campus) }}" data-ajax-form data-refresh-url="{{ route('campuses.index') }}" data-confirm-delete="¿Eliminar la sede {{ $campus->name }}?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm" type="submit" aria-label="Eliminar {{ $campus->name }}"><i class="ti ti-trash" aria-hidden="true"></i></button>
                                    </form>
                                </div>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="4" message="No hay sedes registradas." />
                @endforelse
            </tbody>
        </table>
        <x-slot:footer>{{ $campuses->links() }}</x-slot:footer>
    </x-ui.table-card>
@endsection
