@extends('layouts.admin')

@section('title', 'Habitaciones | '.config('app.name', 'Base Admin'))
@section('page-title', $space->name ?: 'Alojamiento compartido')
@section('page-subtitle', 'Habitaciones internas')

@section('content')
    <div data-refresh-container>
    @include('spaces.shared.partials.stepper')
    <div class="row g-3">
        <div class="col-lg-5">
            <x-ui.card title="Agregar habitacion">
                <div class="card-body">
                    <form method="POST" action="{{ route('spaces.shared.rooms.store', $space) }}" data-ajax-form novalidate>
                        @csrf
                        @include('spaces.shared.partials.room-fields', ['room' => null])
                        <button class="btn btn-primary mt-3" type="submit">Agregar habitacion</button>
                    </form>
                </div>
            </x-ui.card>
        </div>
        <div class="col-lg-7">
            <x-ui.table-card title="Habitaciones registradas">
                <table class="table align-middle">
                    <thead><tr><th>Habitacion</th><th>Baño</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                        @forelse ($space->rooms as $room)
                            <tr>
                                <td><div class="fw-semibold">{{ $room->name ?: $room->title }}</div></td>
                                <td>{{ $room->bathroomType?->name }}</td>
                                <td><span class="badge text-bg-{{ $room->status === 'active' ? 'success' : 'secondary' }}">{{ $room->status }}</span></td>
                                <td class="text-end">
                                    <form class="d-inline" method="POST" action="{{ route('spaces.shared.rooms.destroy', [$space, $room]) }}" data-ajax-form data-confirm-delete="Eliminar habitacion?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm" type="submit">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <x-ui.empty-row colspan="4" message="Agrega al menos una habitacion para poder publicar." />
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table-card>
        </div>
    </div>
    <div class="d-flex justify-content-between mt-4">
        <a class="btn btn-outline-secondary" href="{{ route('spaces.shared.details.edit', $space) }}">Volver</a>
        <a class="btn btn-primary" href="{{ route('spaces.shared.beds.edit', $space) }}">Continuar</a>
    </div>
    </div>
@endsection
