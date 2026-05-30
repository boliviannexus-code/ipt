@extends('layouts.admin')

@section('title', 'Camas | '.config('app.name', 'Base Admin'))
@section('page-title', $space->name ?: 'Alojamiento compartido')
@section('page-subtitle', 'Camas por habitacion')

@section('content')
    <div data-refresh-container>
    @include('spaces.shared.partials.stepper')
    @foreach ($space->rooms as $room)
        <x-ui.card :title="$room->title" class="mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end mb-3" method="POST" action="{{ route('spaces.shared.beds.store', [$space, $room]) }}" data-ajax-form novalidate>
                    @csrf
                    <div class="col-md-7">
                        <label class="form-label">Tipo de cama</label>
                        <select class="form-select" name="bed_type_id" required>
                            <option value="">Seleccionar</option>
                            @foreach ($bedTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }} ({{ $type->capacity }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Cantidad</label>
                        <input class="form-control" name="quantity" type="number" min="1" value="1" required>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" type="submit">Agregar</button>
                    </div>
                </form>
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Cama</th><th>Cantidad</th><th>Capacidad/cama</th><th>Total</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($room->beds as $bed)
                            <tr>
                                <td>{{ $bed->bedType?->name }}</td>
                                <td>{{ $bed->quantity }}</td>
                                <td>{{ $bed->capacity_per_bed }}</td>
                                <td>{{ $bed->total_capacity }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('spaces.shared.beds.destroy', [$space, $room, $bed]) }}" data-ajax-form data-confirm-delete="Quitar cama?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm" type="submit">Quitar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <x-ui.empty-row colspan="5" message="Esta habitacion aun no tiene camas." />
                        @endforelse
                    </tbody>
                </table>
                <div class="text-body-secondary small mt-2">Capacidad calculada: {{ $room->max_capacity ?? 0 }}</div>
            </div>
        </x-ui.card>
    @endforeach
    <div class="d-flex justify-content-between mt-4">
        <a class="btn btn-outline-secondary" href="{{ route('spaces.shared.rooms.edit', $space) }}">Volver</a>
        <a class="btn btn-primary" href="{{ route('spaces.shared.room-services.edit', $space) }}">Continuar</a>
    </div>
    </div>
@endsection
