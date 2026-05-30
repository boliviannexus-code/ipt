@extends('layouts.admin')

@section('title', $metadata['label'].' | '.config('app.name', 'Base Admin'))
@section('page-title', 'Catalogos de alojamientos')
@section('page-subtitle', $metadata['label'])

@section('content')
    <div class="row g-3">
        <div class="col-lg-3">
            <div class="list-group">
                @foreach ($catalogs as $catalogKey => $catalogMeta)
                    <a class="list-group-item list-group-item-action d-flex align-items-center justify-content-between {{ $catalogKey === $catalog ? 'active' : '' }}" href="{{ route('admin.accommodation-catalogs.index', $catalogKey) }}">
                        <span>{{ $catalogMeta['label'] }}</span>
                        @if ($catalogMeta['has_capacity'])
                            <i class="ti ti-bed"></i>
                        @else
                            <i class="ti ti-list"></i>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
        <div class="col-lg-9">
            <x-ui.table-card :title="$metadata['label']" data-refresh-container>
                <x-slot:actions>
                    <a class="btn btn-primary btn-sm" href="{{ route('admin.accommodation-catalogs.create', $catalog) }}" data-modal-url="{{ route('admin.accommodation-catalogs.create', $catalog) }}" data-modal-title="Nuevo registro">
                        <i class="ti ti-plus me-1"></i>Nuevo
                    </a>
                </x-slot:actions>

                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Slug</th>
                            @if ($metadata['has_capacity'])
                                <th>Capacidad</th>
                            @endif
                            <th>Descripcion</th>
                            <th>Estado</th>
                            <th>Orden</th>
                            <th>Uso</th>
                            <th>Creado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $record)
                            @php
                                $isProtected = \App\Support\AccommodationCatalogRegistry::isProtected($catalog, $record);
                            @endphp
                            <tr @class(['table-secondary' => $record->trashed()])>
                                <td>
                                    <div class="fw-semibold">{{ $record->name }}</div>
                                    @if ($isProtected)
                                        <span class="badge text-bg-info">Base</span>
                                    @endif
                                </td>
                                <td><code>{{ $record->slug }}</code></td>
                                @if ($metadata['has_capacity'])
                                    <td>{{ $record->capacity }}</td>
                                @endif
                                <td class="text-body-secondary">{{ str($record->description ?: '-')->limit(70) }}</td>
                                <td>
                                    @if ($record->trashed())
                                        <span class="badge text-bg-danger">Eliminado</span>
                                    @else
                                        <span class="badge text-bg-{{ $record->is_active ? 'success' : 'secondary' }}">{{ $record->is_active ? 'Activo' : 'Inactivo' }}</span>
                                    @endif
                                </td>
                                <td>{{ $record->sort_order ?? '-' }}</td>
                                <td>
                                    <span class="badge text-bg-{{ $record->usage_count > 0 ? 'warning' : 'light' }}">{{ $record->usage_count }}</span>
                                </td>
                                <td>{{ $record->created_at?->format('Y-m-d') }}</td>
                                <td class="text-end">
                                    @if ($record->trashed())
                                        <form class="d-inline" method="POST" action="{{ route('admin.accommodation-catalogs.restore', [$catalog, $record->id]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-outline-success btn-sm" type="submit"><i class="ti ti-restore me-1"></i>Restaurar</button>
                                        </form>
                                    @else
                                        <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.accommodation-catalogs.edit', [$catalog, $record->id]) }}" data-modal-url="{{ route('admin.accommodation-catalogs.edit', [$catalog, $record->id]) }}" data-modal-title="Editar registro">
                                            <i class="ti ti-edit me-1"></i>Editar
                                        </a>
                                        <form class="d-inline" method="POST" action="{{ route('admin.accommodation-catalogs.toggle', [$catalog, $record->id]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-outline-{{ $record->is_active ? 'warning' : 'success' }} btn-sm" type="submit" @disabled($record->is_active && $isProtected)>
                                                <i class="ti ti-power me-1"></i>{{ $record->is_active ? 'Deshabilitar' : 'Activar' }}
                                            </button>
                                        </form>
                                        @if ($record->usage_count > 0 || $isProtected)
                                            <button class="btn btn-outline-danger btn-sm" type="button" disabled title="No se puede eliminar">
                                                <i class="ti ti-trash me-1"></i>Eliminar
                                            </button>
                                        @else
                                            <form class="d-inline" method="POST" action="{{ route('admin.accommodation-catalogs.destroy', [$catalog, $record->id]) }}" data-confirm-delete="Eliminar registro?">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger btn-sm" type="submit"><i class="ti ti-trash me-1"></i>Eliminar</button>
                                            </form>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-ui.empty-row :colspan="$metadata['has_capacity'] ? 9 : 8" message="No hay registros en este catalogo." />
                        @endforelse
                    </tbody>
                </table>

                <x-slot:footer>{{ $records->links() }}</x-slot:footer>
            </x-ui.table-card>
        </div>
    </div>
@endsection
