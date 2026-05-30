@extends('layouts.admin')

@section('title', 'Servicios de habitacion | '.config('app.name', 'Base Admin'))
@section('page-title', $space->name ?: 'Alojamiento compartido')
@section('page-subtitle', 'Servicios individuales')

@section('content')
    <div data-refresh-container>
    @include('spaces.shared.partials.stepper')
    @foreach ($space->rooms as $room)
        <x-ui.card :title="$room->title" class="mb-3">
            <div class="card-body">
                <form method="POST" action="{{ route('spaces.shared.room-services.store', [$space, $room]) }}" data-ajax-form>
                    @csrf
                    @method('PUT')
                    @php($selected = $room->roomServices->pluck('id'))
                    <div class="row g-2">
                        @foreach ($roomServices as $service)
                            <div class="col-sm-6 col-lg-4">
                                <label class="form-check border rounded p-2 ps-5 bg-light h-100">
                                    <input class="form-check-input" name="room_services[]" type="checkbox" value="{{ $service->id }}" @checked($selected->contains($service->id))>
                                    <span class="form-check-label">{{ $service->name }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <button class="btn btn-primary mt-3" type="submit">Guardar servicios</button>
                </form>
            </div>
        </x-ui.card>
    @endforeach
    <div class="d-flex justify-content-between mt-4">
        <a class="btn btn-outline-secondary" href="{{ route('spaces.shared.beds.edit', $space) }}">Volver</a>
        <a class="btn btn-primary" href="{{ route('spaces.shared.photos.edit', $space) }}">Continuar</a>
    </div>
    </div>
@endsection
