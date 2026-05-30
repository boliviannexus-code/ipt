@extends('layouts.admin')

@section('title', 'Servicios generales | '.config('app.name', 'Base Admin'))
@section('page-title', $space->name ?: 'Alojamiento compartido')
@section('page-subtitle', 'Servicios generales')

@section('content')
    <div data-refresh-container>
    @include('spaces.shared.partials.stepper')
    <x-ui.card title="Servicios generales">
        <div class="card-body">
            <form method="POST" action="{{ route('spaces.shared.services.store', $space) }}" data-ajax-form>
                @csrf
                @method('PUT')
                @php($selected = $space->generalServices->pluck('id'))
                <div class="row g-2">
                    @foreach ($generalServices as $service)
                        <div class="col-sm-6 col-lg-4">
                            <label class="form-check border rounded p-2 ps-5 bg-light h-100">
                                <input class="form-check-input" name="general_services[]" type="checkbox" value="{{ $service->id }}" @checked($selected->contains($service->id))>
                                <span class="form-check-label">{{ $service->name }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>
                <div class="d-flex justify-content-between mt-4">
                    <a class="btn btn-outline-secondary" href="{{ route('spaces.shared.photos.edit', $space) }}">Volver</a>
                    <button class="btn btn-primary" type="submit">Guardar y continuar</button>
                </div>
            </form>
        </div>
    </x-ui.card>
    </div>
@endsection
