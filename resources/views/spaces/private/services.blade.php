@extends('layouts.admin')

@section('title', 'Servicios | '.config('app.name', 'Base Admin'))
@section('page-title', $space->title ?: 'Alojamiento privado')
@section('page-subtitle', 'Servicios generales')

@section('content')
    <div data-refresh-container>
    @include('spaces.private.partials.stepper')

    <x-ui.card title="Servicios generales">
        <div class="card-body">
            <form method="POST" action="{{ route('spaces.private.services.store', $space) }}" data-ajax-form>
                @csrf
                @method('PUT')
                @php
                    $selectedServices = collect(old('general_services', $space->generalServices->pluck('id')->all()))->map(fn ($id) => (int) $id);
                @endphp
                <div class="row g-2">
                    @forelse ($generalServices as $service)
                        <div class="col-sm-6 col-lg-4">
                            <label class="form-check border rounded p-2 ps-5 bg-light h-100">
                                <input class="form-check-input" name="general_services[]" type="checkbox" value="{{ $service->id }}" @checked($selectedServices->contains($service->id))>
                                <span class="form-check-label">{{ $service->name }}</span>
                            </label>
                        </div>
                    @empty
                        <div class="col-12 text-body-secondary">No hay servicios activos disponibles.</div>
                    @endforelse
                </div>
                @error('general_services')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
                @error('general_services.*')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
                <div class="d-flex justify-content-between mt-4">
                    <a class="btn btn-outline-secondary" href="{{ route('spaces.private.photos.edit', $space) }}">Volver</a>
                    <button class="btn btn-primary" type="submit">Guardar y continuar</button>
                </div>
            </form>
        </div>
    </x-ui.card>
    </div>
@endsection
